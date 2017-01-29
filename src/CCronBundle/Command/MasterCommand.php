<?php
namespace CCronBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MasterCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName("master");
        $this->addOption("name", null, InputOption::VALUE_REQUIRED, "Hostname");
        $this->addOption("master", null, null, "Force boot as master (for development)");
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $name = $input->getOption("name");
        if ($name) {
            $this->getContainer()->get("hostname_determiner")->set($name);
        }
        $failoverTracker = $this->getContainer()->get("failover_tracker");
        if ($input->getOption("master")) {
            $failoverTracker->setMaster();
        }
        $master = $this->getContainer()->get("master");
        $master->run();
    }
}
