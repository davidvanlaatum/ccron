<?php
namespace AppBundle\Command;

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
        $master = $this->getContainer()->get("master");
        $consumer = $this->getContainer()->get("master_consumer");
        $consumer->addSubQueue($this->getContainer()->get("old_sound_rabbit_mq.keepalive_consumer"));
        $consumer->startConsuming();
        $rpcServer = $this->getContainer()->get("old_sound_rabbit_mq.rpc_servers_consumer");
        $failoverTracker = $this->getContainer()->get("failover_tracker");
        if ($input->getOption("master")) {
            $failoverTracker->setMaster();
        }
        $running = $this->getContainer()->get("running");
        while ($running->isRunning()) {
            $consumer->consume();
            $failoverTracker->check();
            if ($failoverTracker->isMaster()) {
                $consumer->addSubQueue($rpcServer);
                $master->scheduleWork();
            } else {
                $consumer->removeSubQueue($rpcServer);
            }
        }
    }
}
