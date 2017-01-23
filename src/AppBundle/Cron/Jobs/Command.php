<?php
namespace AppBundle\Cron\Jobs;

use AppBundle\Entity\Job as JobEntity;
use AppBundle\Entity\JobRun;
use Symfony\Component\Process\ProcessBuilder;

class Command extends AbstractJob {
    protected $command;
    protected $exitcode;
    protected $output;

    /**
     * Command constructor.
     * @param $command
     */
    public function __construct($command) {
        $this->command = $command;
    }

    public static function build(JobEntity $job) {
        $rt = new Command($job->getCommand());
        parent::fillJob($rt, $job);
        return $rt;
    }

    function execute() {
        $builder = new ProcessBuilder(["sh", "-c", $this->command]);
        $process = $builder->getProcess();
        $process->run(function ($type, $output) {
            $this->output .= $output;
        });
        $this->exitcode = $process->getExitCode();
    }

    /**
     * @return mixed
     */
    public function getOutput() {
        return $this->output;
    }

    public function fillInLog(JobRun $log) {
        $log->setOutput($this->output);
    }
}
