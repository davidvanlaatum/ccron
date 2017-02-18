<?php
namespace CCronBundle\Cron;

use CCronBundle\Cron\Jobs\Job;
use CCronBundle\Entity\JobRun;

class TrackedJob {
    protected $job;
    protected $worker;
    protected $attachment;
    protected $run;
    protected $started;

    /**
     * TrackedJob constructor
     * @param Job $job
     * @param mixed $worker
     * @param $attachment
     * @param float $started
     */
    public function __construct(Job $job, $worker, $attachment, $started) {
        $this->job = $job;
        $this->worker = $worker;
        $this->attachment = $attachment;
        $this->started = $started;
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

    public function setLogEntity(JobRun $run) {
        $this->run = $run;
    }

    /**
     * @return JobRun
     */
    public function getLogEntity() {
        return $this->run;
    }

    /**
     * @return float
     */
    public function getStarted() {
        return $this->started;
    }
}
