<?php
namespace CCronBundle\Command;

use CCronBundle\Events\Control\Shutdown;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShutdownCommand extends ContainerAwareCommand {
    protected function configure() {
        $this->setName("shutdown");
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->getContainer()->get("event_sender")->send(new Shutdown());
    }
}
