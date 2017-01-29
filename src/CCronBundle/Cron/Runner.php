<?php

namespace CCronBundle\Cron;

use CCronBundle\Cron\Jobs\Job;
use Doctrine\ORM\EntityManager;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use QXS\WorkerPool\WorkerPool;
use Symfony\Bridge\Monolog\Logger;

class Runner implements ConsumerInterface {

    /** @var WorkerPool */
    protected $workerPool;
    /** @var JobTracker */
    protected $jobTracker;
    /** @var Logger */
    protected $logger;
    /** @var MultiConsumer */
    protected $multiConsumer;
    /** @var Consumer */
    protected $cronConsumer;
    /** @var Consumer */
    protected $controlConsumer;
    /** @var Running */
    protected $running;
    /** @var EntityManager */
    protected $entityManager;
    /** @var int */
    protected $workers;

    /** @var Consumer */
    public function setWorkerPool(WorkerPool $workerPool) {
        $this->workerPool = $workerPool;
    }

    public function setJobTracker(JobTracker $jobTracker) {
        $this->jobTracker = $jobTracker;
    }

    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }

    public function setMultiConsumer(MultiConsumer $multiConsumer) {
        $this->multiConsumer = $multiConsumer;
    }

    public function setRunning(Running $running) {
        $this->running = $running;
    }

    public function setControlConsumer(Consumer $controlConsumer) {
        $this->controlConsumer = $controlConsumer;
    }

    public function setCronConsumer(Consumer $cronConsumer) {
        $this->cronConsumer = $cronConsumer;
    }

    public function setEntityManager(EntityManager $entityManager) {
        $this->entityManager = $entityManager;
    }

    /**
     * @param int $workers
     */
    public function setWorkers($workers) {
        $this->workers = $workers;
    }

    public function execute(AMQPMessage $message) {
        $expired = false;
        if ($message->has("application_headers")) {
            /** @var AMQPTable $headers */
            $headers = $message->get("application_headers");
            foreach ($headers as $key => $value) {
                if ($key == "expires-at") {
                    if (new \DateTime('@' . $value[1]) < new \DateTime()) {
                        $expired = true;
                    }
                    break;
                }
            }
        }
        $job = @unserialize($message->getBody());
        if ($job instanceof Job) {
            if (!$expired) {
                $pid = $this->workerPool->run(serialize($job));
                $this->jobTracker->jobStarted($pid, $job, $message);
            } else {
                $this->logger->warn("Dropping expired job", ['name' => $job->getName(), 'id' => $job->getId()]);
                $message->get("channel")->basic_ack($message->delivery_info['delivery_tag']);
            }
        } else {
            $this->logger->warn("Got invalid queue item", [$message->getBody()]);
        }
        return true;
    }

    public function run() {
        $this->workerPool->create(new Worker());
        $this->multiConsumer->startConsuming();
        $this->multiConsumer->addSubQueue($this->cronConsumer);
        $this->multiConsumer->addSubQueue($this->controlConsumer);
        $this->multiConsumer->setQosOptions(0, $this->workers, false);
        while ($this->running->isRunning()) {
            $this->multiConsumer->consume();
            $this->checkForCompleteJobs();
            $this->entityManager->clear();
        }
        $this->multiConsumer->stopConsuming();
        $shutdown_start = gettimeofday();
        while ($this->workerPool->getBusyWorkers() > 0 && $shutdown_start + 30 > gettimeofday()) {
            $this->checkForCompleteJobs();
            usleep(100000);
        }
        $this->workerPool->destroy();
    }

    protected function checkForCompleteJobs() {
        if ($this->workerPool->hasResults()) {
            foreach ($this->workerPool as $val) {
                /** @var AMQPMessage $msg */
                $msg = null;
                $requeue = false;
                if (isset($val['data'])) {
                    $msg = $this->jobTracker->jobFinished($val['pid'], unserialize($val['data']));
                } elseif (isset($val['workerException'])) {
                    $requeue = true;
                    $msg = $this->jobTracker->jobFinished($val['pid']);
                    $this->logger->error("WORKER EXCEPTION: " . $val['workerException']['class'] . ": " . $val['workerException']['message'] . "\n" . $val['workerException']['trace']);
                } elseif (isset($val['poolException'])) {
                    $requeue = true;
                    $msg = $this->jobTracker->jobFinished($val['pid']);
                    $this->logger->error("POOL EXCEPTION: " . $val['poolException']['class'] . ": " . $val['poolException']['message'] . "\n" . $val['poolException']['trace']);
                }
                if ($msg) {
                    if ($requeue) {
                        $msg->get("channel")->basic_nack($msg->delivery_info['delivery_tag'], false, true);
                    } else {
                        $msg->get("channel")->basic_ack($msg->delivery_info['delivery_tag']);
                    }
                }
            }
        }
    }
}
