<?php

namespace CCronBundle\Cron;


use CCronBundle\Clock;
use CCronBundle\Cron\Jobs\AbstractJob;
use CCronBundle\Entity\Job;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Psr\Log\LoggerInterface;

class JobQueuer {

    /** @var Producer */
    protected $cronProducer;
    /** @var LoggerInterface */
    protected $logger;
    /** @var Clock */
    protected $clock;

    public function __construct(Clock $clock) {
        $this->clock = $clock;
    }

    public function setCronProducer(Producer $cronProducer) {
        $this->cronProducer = $cronProducer;
    }

    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function runJob(Job $job, \DateTime $nextRun) {
        $this->logger->info("Queueing job", ['name' => $job->getName()]);
        $command = AbstractJob::factory($job);
        $this->cronProducer->publish(serialize($command), '',
            ['x-message-ttl' => ($nextRun->getTimestamp() - $this->clock->getTime())], ['expires-at' => $nextRun->getTimestamp()]);
    }
}
