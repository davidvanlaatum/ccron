<?php
namespace AppBundle\Events;

use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class EventSender implements ContainerAwareInterface {
    use ContainerAwareTrait;

    public function send(Event $event) {
        $class = str_replace(["AppBundle\\Events\\", "\\"], ["", "."], get_class($event));
        $this->container->get("logger")->debug("Sending event", ["topic" => $class]);
        $this->container->get("old_sound_rabbit_mq.events_producer")->publish(serialize($event), $class);
    }

    /** @return Event */
    public function receive(AMQPMessage $msg) {
        return unserialize($msg->getBody());
    }
}
