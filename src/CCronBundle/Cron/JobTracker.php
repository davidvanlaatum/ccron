<?php
namespace CCronBundle\Cron;

use CCronBundle\Cron\Jobs\Command;
use CCronBundle\Cron\Jobs\Job;
use CCronBundle\Entity\JobRun;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class JobTracker implements ContainerAwareInterface {
    use ContainerAwareTrait;
    /** @var TrackedJob[] */
    protected $inProgressJobs = [];

    function jobStarted($workerId, Job $job, $attachment = null) {
        $this->container->get("logger")->debug("Job Started", ["worker" => $workerId, "id" => $job->getId(), "name" => $job->getName()]);
        $this->inProgressJobs[$workerId] = new TrackedJob($job, $workerId, $attachment);
    }

    function jobFinished($workerId, Job $job = null) {
        $rt = null;
        $trackedJob = $this->inProgressJobs[$workerId];
        $rt = $trackedJob->getAttachment();
        if ($job == null) {
            $job = $trackedJob->getJob();
        }
        $this->container->get("logger")->debug("Job Finished", ["worker" => $workerId, "id" => $job->getId(), "name" => $job->getName(), "output" => $job instanceof Command ? $job->getOutput() : null]);

        $em = $this->container->get("doctrine.orm.entity_manager");
        $em->transactional(function (EntityManager $em) use ($job) {
            /** @var \CCronBundle\Entity\Job $dbjob */
            $dbjob = $em->find(\CCronBundle\Entity\Job::class, $job->getId(), LockMode::PESSIMISTIC_WRITE);
            $dbjob->setLastRun(new \DateTime());
            $dbjob->setLastRunTime(1);
            $em->persist($dbjob);
            $log = new JobRun();
            $log->setJob($dbjob);
            $log->setTime(new \DateTime());
            $job->fillInLog($log);
            $log->setHost($this->container->get("hostname_determiner")->get());
            $em->persist($log);
            $em->flush();
        });
        return $rt;
    }
}

class TrackedJob {
    protected $job;
    protected $worker;
    protected $attachment;

    /**
     * TrackedJob constructor
     * @param Job $job
     * @param mixed $worker
     */
    public function __construct(Job $job, $worker, $attachment) {
        $this->job = $job;
        $this->worker = $worker;
        $this->attachment = $attachment;
    }

    /**
     * @return Job
     */
    public function getJob() {
        return $this->job;
    }

    /**
     * @return mixed
     */
    public function getWorker() {
        return $this->worker;
    }

    /**
     * @return mixed
     */
    public function getAttachment() {
        return $this->attachment;
    }
}
