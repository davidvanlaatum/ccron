<?php
namespace AppBundle\Cron;


use AppBundle\Cron\Jobs\Command;
use AppBundle\Cron\Jobs\Job;
use AppBundle\Entity\JobRun;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class JobTracker implements ContainerAwareInterface {
    use ContainerAwareTrait;
    /** @var TrackedJob[] */
    protected $inProgressJobs = [];

    function jobStarted($workerId, Job $job) {
        $this->container->get("logger")->debug("Job Started", ["worker" => $workerId, "id" => $job->getId(), "name" => $job->getName()]);
        $this->inProgressJobs[$workerId] = new TrackedJob($job, $workerId);
    }

    function jobFinished($workerId, Job $job = null) {
        if ($job == null) {
            $job = $this->inProgressJobs[$workerId]->getJob();
        }
        $this->container->get("logger")->debug("Job Finished", ["worker" => $workerId, "id" => $job->getId(), "name" => $job->getName(), "output" => $job instanceof Command ? $job->getOutput() : null]);

        $em = $this->container->get("doctrine.orm.entity_manager");
        $em->transactional(function (EntityManager $em) use ($job) {
            $log = new JobRun();
            $log->setJob($em->getReference(\AppBundle\Entity\Job::class, $job->getId()));
            $log->setTime(new \DateTime());
            $job->fillInLog($log);
            $em->persist($log);
            $em->flush($log);
        });
    }
}

class TrackedJob {
    protected $job;
    protected $worker;

    /**
     * TrackedJob constructor
     * @param Job $job
     * @param mixed $worker
     */
    public function __construct(Job $job, $worker) {
        $this->job = $job;
        $this->worker = $worker;
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
}
