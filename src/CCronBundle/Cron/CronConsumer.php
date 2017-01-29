<?php
namespace CCronBundle\Cron;

use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use PhpAmqpLib\Message\AMQPMessage;

class CronConsumer extends Consumer {
    protected $prefetchCount;

    public function setQosOptions($prefetchSize = 0, $prefetchCount = 0, $global = false) {
        $this->prefetchCount = $prefetchCount;
    }

    protected function handleProcessMessage(AMQPMessage $msg, $processFlag) {
    }

    protected function setupConsumer() {
        $this->getChannel()->basic_qos(0, $this->prefetchCount, false);
        parent::setupConsumer();
    }
}
