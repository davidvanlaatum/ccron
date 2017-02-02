<?php
namespace CCronBundle\Cron;

use CCronBundle\Clock;
use CCronBundle\Entity\CurrentState;
use CCronBundle\Entity\Job;
use Cron\CronExpression;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use Psr\Log\LoggerInterface;

class Master {
    /** @var \OldSound\RabbitMqBundle\RabbitMq\Consumer */
    protected $keepaliveConsumer;
    /** @var \OldSound\RabbitMqBundle\RabbitMq\Consumer */
    protected $rpcServer;
    /** @var FailoverTracker */
    protected $failoverTracker;
    /** @var MultiConsumer */
    protected $masterConsumer;
    /** @var EntityManager */
    protected $entityManager;
    /** @var LoggerInterface */
    protected $logger;
    /** @var HostnameDeterminer */
    protected $hostnameDeterminer;
    /** @var Running */
    protected $running;
    /** @var JobQueuer */
    protected $jobQueuer;
    /** @var Clock $clock */
    protected $clock;
    /** @var \DateTime */
    protected $lastDBUpdate;
    /** @var \DateTime */
    protected $lastWorkCheck;
    /** @var \DateInterval */
    protected $pollInterval;
    /** @var \DateInterval */
    protected $updateStatsInterval;

    public function __construct(Clock $clock) {
        $this->clock = $clock;
        $this->lastDBUpdate = $this->lastWorkCheck = $clock->getCurrentDateTime();
        $this->pollInterval = new \DateInterval('PT1S');
        $this->updateStatsInterval = new \DateInterval('PT1S');
    }

    public function setEntityManager(EntityManager $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function setFailoverTracker(FailoverTracker $failoverTracker) {
        $this->failoverTracker = $failoverTracker;
    }

    public function setRPCServer(Consumer $rpcServer) {
        $this->rpcServer = $rpcServer;
    }

    public function setKeepaliveConsumer(Consumer $keepaliveConsumer) {
        $this->keepaliveConsumer = $keepaliveConsumer;
    }

    public function setMasterConsumer(MultiConsumer $masterConsumer) {
        $this->masterConsumer = $masterConsumer;
    }

    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function setJobQueuer(JobQueuer $jobQueuer) {
        $this->jobQueuer = $jobQueuer;
    }

    public function setHostnameDeterminer(HostnameDeterminer $hostnameDeterminer) {
        $this->hostnameDeterminer = $hostnameDeterminer;
    }

    public function setRunning(Running $running) {
        $this->running = $running;
    }

    public function run() {
        $this->masterConsumer->addSubQueue($this->keepaliveConsumer);
        $this->masterConsumer->startConsuming();
        while ($this->running->isRunning()) {
            $this->masterConsumer->consume();
            $this->failoverTracker->check();
            if ($this->failoverTracker->isMaster()) {
                $this->masterConsumer->addSubQueue($this->rpcServer);
                $this->scheduleWork();
            } else {
                $this->masterConsumer->removeSubQueue($this->rpcServer);
            }
            $this->entityManager->clear();
        }
    }

    public function scheduleWork() {
        $now = $this->clock->getCurrentDateTime();
        if ($this->lastDBUpdate < $now->sub($this->updateStatsInterval)) {
            $this->updateStats();
            $this->lastDBUpdate = $now;
        }

        if ($this->lastWorkCheck < $now->sub($this->pollInterval)) {
            $this->checkForWork();
            $this->lastWorkCheck = $now;
        }
    }

    public function updateStats() {
        $this->logger->debug("Updating stats");
        $this->entityManager->transactional(function (EntityManager $em) {
            $state = $em->find(CurrentState::class, 1, LockMode::PESSIMISTIC_WRITE);
            if (!$state) {
                $state = new CurrentState(1);
            }
            $state->setMaster($this->hostnameDeterminer->get());
            $state->setLastUpdated($this->clock->getCurrentDateTime());
            $state->setUptime($this->failoverTracker->getUptime());
            $state->setMasterUptime($this->failoverTracker->getMasterUptime());
            $em->persist($state);
        });
    }

    public function checkForWork() {
        $this->entityManager->transactional(function (EntityManager $em) {
            $now = $this->clock->getCurrentDateTime();
            foreach ($em->getRepository(Job::class)->getWork($now) as $job) {
                $cron = CronExpression::factory($job->getCronSchedule());
                $nextRun = $cron->getNextRunDate($this->clock->getCurrentDateTime());
                if ($job->getNextRun() && $job->getNextRun() <= $now) {
                    $this->jobQueuer->runJob($job, $nextRun);
                }
                if (!$job->getNextRun() || $job->getNextRun() <= $now) {
                    $this->logger->info("Scheduling next run", ['name' => $job->getName(), 'next run' => $nextRun]);
                    $job->setNextRun($nextRun);
                    $em->persist($job);
                    $em->flush($job);
                }
            }
        });
    }
}
