<?php
namespace CCronBundle\Cron\Jobs;

use CCronBundle\Entity\Job as JobEntity;
use CCronBundle\Entity\JobRun;
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
        $builder = new ProcessBuilder(["exec", "sh", "-xc", $this->command]);
        $process = $builder->getProcess();
        var_dump($process->getCommandLine());
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
