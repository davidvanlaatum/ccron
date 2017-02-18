<?php
namespace CCronBundle\Cron;

use CCronBundle\Clock;
use CCronBundle\Cron\Jobs\Command;
use CCronBundle\Cron\Jobs\Job;
use CCronBundle\Entity\JobRun;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

class JobTracker {
    /** @var TrackedJob[] */
    protected $inProgressJobs = [];
    /** @var LoggerInterface */
    protected $logger;
    /** @var EntityManager */
    protected $em;
    /** @var HostnameDeterminer */
    protected $hostnameDeterminer;
    /** @var Clock */
    protected $clock;

    /**
     * JobTracker constructor.
     * @param LoggerInterface $logger
     * @param EntityManager $em
     * @param HostnameDeterminer $hostnameDeterminer
     * @param Clock $clock
     */
    public function __construct(LoggerInterface $logger, EntityManager $em, HostnameDeterminer $hostnameDeterminer, Clock $clock) {
        $this->logger = $logger;
        $this->em = $em;
        $this->hostnameDeterminer = $hostnameDeterminer;
        $this->clock = $clock;
    }

    function jobStarted($workerId, Job $job, $attachment = null) {
        $this->logger->debug("Job Started", ["worker" => $workerId, "id" => $job->getId(), "name" => $job->getName()]);
        $trackedJob = $this->inProgressJobs[$workerId] = new TrackedJob($job, $workerId, $attachment, $this->clock->getTimeOfDay());
        $this->em->transactional(function (EntityManager $em) use ($job, $trackedJob) {
            /** @var \CCronBundle\Entity\Job $dbJob */
            $dbJob = $em->find(\CCronBundle\Entity\Job::class, $job->getId(), LockMode::PESSIMISTIC_WRITE);
            $dbJob->setLastRun($this->clock->getCurrentDateTime());
            $dbJob->setLastRunTime(null);
            $em->persist($dbJob);
            $log = new JobRun();
            $log->setJob($dbJob);
            $log->setTime($this->clock->getCurrentDateTime());
            $log->setHost($this->hostnameDeterminer->get());
            $trackedJob->setLogEntity($log);
            $em->persist($log);
            $em->flush();
        });
    }

    function jobFinished($workerId, Job $job = null) {
        $rt = null;
        $trackedJob = $this->inProgressJobs[$workerId];
        $rt = $trackedJob->getAttachment();
        if ($job == null) {
            $job = $trackedJob->getJob();
        }
        $runTime = $this->clock->getTimeOfDay() - $trackedJob->getStarted();
        $this->logger->debug("Job Finished", ["worker" => $workerId, "id" => $job->getId(), "name" => $job->getName(), "runTime" => $runTime, "output" => $job instanceof Command ? $job->getOutput() : null]);

        $this->em->transactional(function (EntityManager $em) use ($job, $trackedJob, $runTime) {
            /** @var \CCronBundle\Entity\Job $dbJob */
            $dbJob = $em->find(\CCronBundle\Entity\Job::class, $job->getId(), LockMode::PESSIMISTIC_WRITE);
            $log = $trackedJob->getLogEntity();
            $dbJob->setLastRunTime($runTime);
            $em->persist($dbJob);
            /** @var JobRun $log */
            $log = $em->merge($log);
            $job->fillInLog($log);
            $log->setRunTime($runTime);
            $log->setJob($dbJob);
            $em->persist($log);
            $em->flush();
        });
        return $rt;
    }
}
