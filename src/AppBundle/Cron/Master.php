<?php
namespace AppBundle\Cron;

use AppBundle\Cron\Jobs\AbstractJob;
use AppBundle\Entity\CurrentState;
use AppBundle\Entity\Job;
use Cron\CronExpression;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Master implements ContainerAwareInterface {
    use ContainerAwareTrait;
    protected $lastDBUpdate;
    protected $lastWorkCheck;
    protected $pollTime = 10;
    protected $updateStatsTime = 10;

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
        $this->container->get("logger")->debug("Updating stats");
        $em = $this->container->get("doctrine.orm.default_entity_manager");
        $em->transactional(function (EntityManager $em) {
            $failoverTracker = $this->container->get("failover_tracker");
            $state = $em->find(CurrentState::class, 1, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);
            if (!$state) {
                $state = new CurrentState(1);
            }
            $state->setMaster($this->container->get("hostname_determiner")->get());
            $state->setLastUpdated(new \DateTime());
            $state->setUptime($failoverTracker->getUptime());
            $state->setMasterUptime($failoverTracker->getMasterUptime());
            $em->persist($state);
        });
    }

    public function checkForWork() {
        $em = $this->container->get("doctrine.orm.default_entity_manager");
        $em->transactional(function (EntityManager $em) {
            $query = $em->getRepository(Job::class)->createNamedQuery("poll.work");
            $query->setLockMode(LockMode::PESSIMISTIC_WRITE);
            $logger = $this->container->get("logger");
            $now = new \DateTime();
            /** @var \AppBundle\Entity\Job $job */
            foreach ($query->execute(["now" => $now]) as $job) {
                $cron = CronExpression::factory($job->getCronschedule());
                $nextRun = $cron->getNextRunDate();
                if ($job->getNextRun() && $job->getNextRun() <= $now) {
                    $this->runJob($job, $nextRun);
                }
                if (!$job->getNextRun() || $job->getNextRun() <= $now) {
                    $logger->info("Scheduling next run", ['name' => $job->getName(), 'next run' => $nextRun]);
                    $job->setNextRun($nextRun);
                    $em->persist($job);
                    $em->flush($job);
                }
            }
        });
    }

    public function runJob(Job $job, \DateTime $nextRun) {
        $logger = $this->container->get("logger");
        $logger->info("Queueing job", ['name' => $job->getName()]);
        $command = AbstractJob::factory($job);
        $this->container->get("old_sound_rabbit_mq.cron_producer")->publish(serialize($command), '',
            ['x-message-ttl' => ($nextRun->getTimestamp() - (int)gettimeofday())], ['expires-at' => $nextRun->getTimestamp()]);
    }
}
