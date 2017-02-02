<?php
namespace CCronBundle\Cron;

use CCronBundle\Clock;
use CCronBundle\Events\AbstractEvent;
use CCronBundle\Events\Control\ControlEvent;
use CCronBundle\Events\Event;
use CCronBundle\Events\HA\ElectionInProgressEvent;
use CCronBundle\Events\HA\ElectionStartEvent;
use CCronBundle\Events\HA\KeepaliveEvent;
use CCronBundle\Events\HA\RegisterForElectionEvent;
use DateTime;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class FailoverTracker implements ContainerAwareInterface, ConsumerInterface {
    use ContainerAwareTrait;
    /** @var Clock */
    protected $clock;
    protected $master;
    protected $masterSince;
    protected $upSince;
    protected $currentMaster;
    protected $lastKeepalive;
    protected $electionInProgress;
    protected $electionStartedAt;
    protected $electionStartedBy;
    /** @var RegisterForElectionEvent[] */
    protected $electionHosts = [];
    protected $keepaliveTime = 5;

    /**
     * FailoverTracker constructor.
     */
    public function __construct(Clock $clock) {
        $this->upSince = $this->lastKeepalive = gettimeofday(true);
    }

    /**
     * @param int $keepaliveTime
     */
    public function setKeepaliveTime($keepaliveTime) {
        $this->keepaliveTime = $keepaliveTime;
    }

    public function setMaster() {
        $this->startMaster();
    }

    protected function startMaster() {
        $this->container->get("logger")->info("Now master");
        $this->master = true;
        $this->masterSince = gettimeofday(true);
        $this->sendKeepalive();
    }

    protected function sendKeepalive() {
        $this->sendEvent($this->buildKeepaliveEvent());
    }

    protected function sendEvent(Event $event) {
        $this->container->get("event_sender")->send($event);
    }

    protected function buildKeepaliveEvent() {
        $rt = new KeepaliveEvent();
        return $this->fillEvent($rt);
    }

    protected function fillEvent(Event $event) {
        if ($event instanceof AbstractEvent) {
            $event->setHost($this->getHostname());
            $event->setUptime(gettimeofday(true) - $this->upSince);
        }
        return $event;
    }

    protected function getHostname() {
        return $this->container->get("hostname_determiner")->get();
    }

    public function check() {
        $date = new DateTime();
        $date->setTimestamp((int)$this->lastKeepalive);
        $now = gettimeofday(true);
        if ($this->master) {
            if ($this->lastKeepalive < $now - $this->keepaliveTime) {
                $this->sendKeepalive();
                $this->lastKeepalive = $now;
            }
        } else if (!$this->electionInProgress && $this->lastKeepalive < $now - ($this->keepaliveTime * 3)) {
            $this->startElection();
        } else if ($this->electionInProgress) {
            if ($this->electionStartedAt < $now - ($this->keepaliveTime * 3)) {
                $this->finishElection();
            } else if ($this->lastKeepalive < $now - $this->keepaliveTime) {
                $this->electionInProgress();
            }
        }
    }

    protected function startElection() {
        $this->sendEvent($this->buildElectionStartEvent());
    }

    protected function buildElectionStartEvent() {
        $rt = new ElectionStartEvent();
        return $this->fillEvent($rt);
    }

    protected function finishElection() {
        usort($this->electionHosts, function (RegisterForElectionEvent $e1, RegisterForElectionEvent $e2) {
            if ($e1->getRandom() != $e2->getRandom()) {
                return $e1->getRandom() > $e2->getHost() ? 1 : -1;
            } else {
                return strcmp($e1->getHost(), $e2->getHost());
            }
        });
        $newMaster = current($this->electionHosts);
        $this->electionInProgress = false;
        $this->electionStartedAt = null;
        $this->electionStartedBy = null;
        $this->electionHosts = [];

        if ($newMaster) {
            $this->container->get("logger")->info("Election complete", ['newMaster' => $newMaster->getHost()]);
            if ($newMaster->getHost() == $this->getHostname()) {
                $this->startMaster();
            } else {
                $this->container->get("logger")->info("Lost election");
            }
        }
    }

    protected function electionInProgress() {
        if ($this->getHostname() == $this->electionStartedBy) {
            $this->sendEvent($this->fillEvent(new ElectionInProgressEvent()));
        }
    }

    /**
     * @param AMQPMessage $msg The message
     * @return mixed false to reject and requeue, any other value to acknowledge
     */
    public function execute(AMQPMessage $msg) {
        $event = $this->container->get("event_sender")->receive($msg);
        $this->container->get("logger")->debug("Recived event", ["class" => get_class($event), "host" => $event instanceof AbstractEvent ? $event->getHost() : null]);
        if ($event instanceof KeepaliveEvent) {
            $this->lastKeepalive = gettimeofday(true);
            if ($event->getHost() != $this->currentMaster) {
                if ($this->currentMaster) {
                    $this->container->get("logger")->info("Master changed", ["old" => $this->currentMaster, "new" => $event->getHost()]);
                }
                $this->currentMaster = $event->getHost();
            }
            if ($this->isMaster() && $event->getHost() != $this->getHostname()) {
                $this->container->get("logger")->warn("Muliple masters shutting down", ["other" => $event->getHost()]);
                $this->stopMaster();
            }
        } else if ($event instanceof ElectionStartEvent || $event instanceof ElectionInProgressEvent) {
            if (!$this->electionInProgress) {
                $this->stopMaster();
                $this->electionInProgress = true;
                $this->electionStartedAt = gettimeofday(true);
                $this->electionStartedBy = $event->getHost();
                $this->container->get("logger")->info("Starting election", ["host" => $this->electionStartedBy]);
                if ($event instanceof ElectionStartEvent) {
                    $this->registerForElection();
                }
            } else if ($event instanceof ElectionInProgressEvent) {
                $this->container->get("logger")->debug("Election still in progress", ["hosts" => count($this->electionHosts)]);
                $this->lastKeepalive = gettimeofday(true);
            }
        } else if ($event instanceof RegisterForElectionEvent) {
            $this->container->get("logger")->info("Adding host to election", ["host" => $event->getHost()]);
            $this->electionHosts[$event->getHost()] = $event;
        } else if ($event instanceof ControlEvent) {
            $this->container->get("events")->process($event);
        } else {
            $this->container->get("logger")->warn("Unhandled event", [get_class($event)]);
        }
        return true;
    }

    public function isMaster() {
        return $this->master;
    }

    protected function stopMaster() {
        if ($this->master) {
            $this->container->get("logger")->info("No longer master");
        }
        $this->master = false;
        $this->masterSince = null;
    }

    protected function registerForElection() {
        $this->sendEvent($this->fillEvent(new RegisterForElectionEvent()));
        $this->container->get("logger")->info("Registering for election");
    }

    /**
     * @return float
     */
    public function getUptime() {
        return gettimeofday(true) - $this->upSince;
    }

    public function getMasterUptime() {
        return gettimeofday(true) - $this->masterSince;
    }
}
