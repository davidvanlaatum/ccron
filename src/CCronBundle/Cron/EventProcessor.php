<?php
namespace CCronBundle\Cron;

use CCronBundle\Events\Control\Shutdown;
use CCronBundle\Events\Event;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class EventProcessor implements ConsumerInterface, ContainerAwareInterface {
    use ContainerAwareTrait;

    /**
     * @param AMQPMessage $msg The message
     * @return mixed false to reject and requeue, any other value to acknowledge
     */
    public function execute(AMQPMessage $msg) {
        $this->process($this->container->get("event_sender")->receive($msg));
        return true;
    }

    public function process(Event $event) {
        if ($event instanceof Shutdown) {
            $this->container->get("logger")->info("Recived shutdown message");
            $this->container->get("running")->shutdown();
        }
    }
}
