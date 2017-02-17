<?php
namespace CCronBundle\Command;

use CCronBundle\Cron\FailoverTracker;
use CCronBundle\Cron\HostnameDeterminer;
use CCronBundle\Cron\Master;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MasterCommand extends DaemonCommand {

    /** @var FailoverTracker */
    protected $failoverTracker;
    /** @var Master */
    protected $master;

    /**
     * MasterCommand constructor.
     * @param FailoverTracker $failoverTracker
     * @param Master $master
     * @param HostnameDeterminer $hostnameDeterminer
     */
    public function __construct(FailoverTracker $failoverTracker, Master $master, HostnameDeterminer $hostnameDeterminer) {
        parent::__construct($hostnameDeterminer);
        $this->failoverTracker = $failoverTracker;
        $this->master = $master;
    }


    /**
     * {@inheritDoc}
     */
    protected function configure() {
        parent::configure();
        $this->setName("master");
        $this->addOption("master", null, null, "Force boot as master (for development)");
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        if ($input->getOption("master")) {
            $this->failoverTracker->setMaster();
        }
        $this->master->run();
    }
}
