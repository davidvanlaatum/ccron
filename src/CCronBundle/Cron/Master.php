<?php
namespace CCronBundle\Cron;

use CCronBundle\Cron\Jobs\AbstractJob;
use CCronBundle\Entity\CurrentState;
use CCronBundle\Entity\Job;
use Cron\CronExpression;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Symfony\Bridge\Monolog\Logger;

class Master {
    /** @var \OldSound\RabbitMqBundle\RabbitMq\Consumer */
    protected $keepaliveConsumer;

    /** @var \OldSound\RabbitMqBundle\RabbitMq\Consumer */
    protected $rpcServer;

    /** @var FailoverTracker */
    protected $failoverTracker;

    /** @var MultiConsumer */
    protected $master_consumer;

    /** @var EntityManager */
    protected $entityManager;
    /** @var Logger */
    protected $logger;
    /** @var HostnameDeterminer */
    protected $hostnameDeterminer;
    /** @var Running */
    protected $running;
    /** @var Producer */
    protected $cronProducer;
    protected $lastDBUpdate;
    protected $lastWorkCheck;
    protected $pollTime = 10;
    protected $updateStatsTime = 10;

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

    public function setMasterConsumer(MultiConsumer $master_consumer) {
        $this->master_consumer = $master_consumer;
    }

    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }

    public function setCronProducer(Producer $cronProducer) {
        $this->cronProducer = $cronProducer;
    }

    public function setHostnameDeterminer(HostnameDeterminer $hostnameDeterminer) {
        $this->hostnameDeterminer = $hostnameDeterminer;
    }

    public function setRunning(Running $running) {
        $this->running = $running;
    }

    public function run() {
        $this->master_consumer->addSubQueue($this->keepaliveConsumer);
        $this->master_consumer->startConsuming();
        while ($this->running->isRunning()) {
            $this->master_consumer->consume();
            $this->failoverTracker->check();
            if ($this->failoverTracker->isMaster()) {
                $this->master_consumer->addSubQueue($this->rpcServer);
                $this->scheduleWork();
            } else {
                $this->master_consumer->removeSubQueue($this->rpcServer);
            }
            $this->entityManager->clear();
        }
    }

    public function scheduleWork() {
        $now = gettimeofday(true);
        if ($this->lastDBUpdate < $now - $this->updateStatsTime) {
            $this->updateStats();
            $this->lastDBUpdate = $now;
        }

        if ($this->lastWorkCheck < $now - $this->pollTime) {
            $this->checkForWork();
            $this->lastWorkCheck = $now;
        }
    }

    protected function updateStats() {
        $this->logger->debug("Updating stats");
        $this->entityManager->transactional(function (EntityManager $em) {
            $state = $em->find(CurrentState::class, 1, LockMode::PESSIMISTIC_WRITE);
            if (!$state) {
                $state = new CurrentState(1);
            }
            $state->setMaster($this->hostnameDeterminer->get());
            $state->setLastUpdated(new \DateTime());
            $state->setUptime($this->failoverTracker->getUptime());
            $state->setMasterUptime($this->failoverTracker->getMasterUptime());
            $em->persist($state);
        });
    }

    public function checkForWork() {
        $this->entityManager->transactional(function (EntityManager $em) {
            $query = $em->getRepository(Job::class)->createNamedQuery("poll.work");
            $query->setLockMode(LockMode::PESSIMISTIC_WRITE);

            $now = new \DateTime();
            /** @var \CCronBundle\Entity\Job $job */
            foreach ($query->execute(["now" => $now]) as $job) {
                $cron = CronExpression::factory($job->getCronSchedule());
                $nextRun = $cron->getNextRunDate();
                if ($job->getNextRun() && $job->getNextRun() <= $now) {
                    $this->runJob($job, $nextRun);
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

    public function runJob(Job $job, \DateTime $nextRun) {
        $this->logger->info("Queueing job", ['name' => $job->getName()]);
        $command = AbstractJob::factory($job);
        $this->cronProducer->publish(serialize($command), '',
            ['x-message-ttl' => ($nextRun->getTimestamp() - (int)gettimeofday())], ['expires-at' => $nextRun->getTimestamp()]);
    }
}
