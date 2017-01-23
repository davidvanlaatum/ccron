<?php
namespace CCronBundle\Cron;

use OldSound\RabbitMqBundle\RabbitMq\BaseConsumer;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class MultiConsumer extends BaseConsumer {
    use ContainerAwareTrait;
    /** @var BaseConsumer[] */
    protected $subConsumers = [];
    protected $consumerIndex = 1;
    protected $setupForConsume = false;

    public function addSubQueue(BaseConsumer $consumer) {
        if (!in_array($consumer, $this->subConsumers)) {
            $this->subConsumers[] = $consumer;
            if ($this->setupForConsume) {
                $this->startConsumingOn($consumer);
            }
        }
    }

    protected function startConsumingOn(BaseConsumer $consumer) {
        if(!$this->container) {
            throw new \Exception("No container");
        }
        $this->container->get("logger")->debug("Now consuming on " . get_class($consumer), [$consumer->queueOptions]);
        if ($consumer->ch != null && $consumer->ch != $this->getChannel()) {
            $consumer->ch->close();
        }
        $consumer->setChannel($this->getChannel());
        $consumer->setConsumerTag(sprintf("%s-%d-%d", $this->container->get("hostname_determiner")->get(), self::myPid(), $this->consumerIndex++));
        $consumer->setupConsumer();
    }

    private static function myPid() {
        if (function_exists("posix_getpid")) {
            return posix_getpid();
        }
        return 0;
    }

    public function removeSubQueue(BaseConsumer $consumer) {
        if (in_array($consumer, $this->subConsumers)) {
            if ($this->setupForConsume) {
                $this->stopConsumingOn($consumer);
            }
            if (($key = array_search($consumer, $this->subConsumers)) !== false) {
                unset($this->subConsumers[$key]);
            } else {
                throw new \Exception("Failed to remove consumer!");
            }
        }
    }

    protected function stopConsumingOn(BaseConsumer $consumer) {
        if(!$this->container) {
            throw new \Exception("No container");
        }
        $this->container->get("logger")->debug("No longer consuming on " . get_class($consumer), [$consumer->queueOptions]);
        $consumer->stopConsuming();
    }

    public function consume() {
        if (!$this->setupForConsume) {
            $this->startConsuming();
        }
        $this->getChannel()->basic_qos(null, 10, true);
        if (count($this->getChannel()->callbacks)) {
            $this->maybeStopConsumer();
            if (!$this->forceStop) {
                try {
                    $this->getChannel()->wait(null, false, $this->getIdleTimeout());
                } catch (AMQPTimeoutException $e) {
                    if (null !== $this->getIdleTimeoutExitCode()) {
                        return $this->getIdleTimeoutExitCode();
                    } else {
                        throw $e;
                    }
                }
            }
        }
    }

    public function startConsuming() {
        foreach ($this->subConsumers as $consumer) {
            $this->startConsumingOn($consumer);
        }
        $this->setupForConsume = true;
    }

    public function stopConsuming() {
        foreach ($this->subConsumers as $consumer) {
            $this->stopConsumingOn($consumer);
        }
    }
}
