<?php

namespace CCronBundle\Cron;

use CCronBundle\Cron\Jobs\Job;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Runner implements ConsumerInterface, ContainerAwareInterface {
    use ContainerAwareTrait;

    public function execute(AMQPMessage $message) {
        if (!$this->container) {
            throw new \Exception("No container!");
        }
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
        $job = unserialize($message->getBody());
        if ($job instanceof Job) {
            if (!$expired) {
                if ($job->preExecute($this->container)) {
                    $pid = $this->container->get("workerpool")->run(serialize($job));
                    $this->container->get("job_tracker")->jobStarted($pid, $job);
                }
            } else {
                $this->container->get("logger")->warn("Dropping expired job", ['name' => $job->getName(), 'id' => $job->getId()]);
            }
        }
    }
}
