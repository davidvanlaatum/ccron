<?php

namespace AppBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CronCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName("cron");
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $workerPool = $this->getContainer()->get("workerpool");
        $workerPool->create($this->getContainer()->get("worker"));

        $consumer = $this->getContainer()->get("master_consumer");
        $consumer->addSubQueue($this->getContainer()->get("old_sound_rabbit_mq.cron_consumer"));
        $consumer->addSubQueue($this->getContainer()->get("old_sound_rabbit_mq.control_consumer"));
        $consumer->startConsuming();
        $running = $this->getContainer()->get("running");
        while ($running->isRunning()) {
            $consumer->consume();
            $this->checkForCompleteJobs();
        }
        $consumer->stopConsuming();
        $shutdown_start = gettimeofday();
        while ($workerPool->getBusyWorkers() > 0 && $shutdown_start + 30 > gettimeofday()) {
            $this->checkForCompleteJobs();
            usleep(100000);
        }
        $workerPool->destroy();
    }

    protected function checkForCompleteJobs() {
        $workerPool = $this->getContainer()->get("workerpool");
        $jobTracker = $this->getContainer()->get("job_tracker");
        if ($workerPool->hasResults()) {
            foreach ($workerPool as $val) {
                if (isset($val['data'])) {
                    $jobTracker->jobFinished($val['pid'], unserialize($val['data']));
                } elseif (isset($val['workerException'])) {
                    $jobTracker->jobFinished($val['pid']);
                    $this->getContainer()->get("logger")->error("WORKER EXCEPTION: " . $val['workerException']['class'] . ": " . $val['workerException']['message'] . "\n" . $val['workerException']['trace']);
                } elseif (isset($val['poolException'])) {
                    $jobTracker->jobFinished($val['pid']);
                    $this->getContainer()->get("logger")->error("POOL EXCEPTION: " . $val['poolException']['class'] . ": " . $val['poolException']['message'] . "\n" . $val['poolException']['trace']);
                }
            }
        }
    }
}
