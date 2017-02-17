<?php
namespace CCronBundle\Command;

use CCronBundle\Cron\HostnameDeterminer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

abstract class DaemonCommand extends Command {

    /** @var HostnameDeterminer */
    protected $hostnameDeterminer;

    /**
     * DaemonCommand constructor.
     * @param HostnameDeterminer $hostnameDeterminer
     */
    public function __construct(HostnameDeterminer $hostnameDeterminer) {
        parent::__construct();
        $this->hostnameDeterminer = $hostnameDeterminer;
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output) {
        $name = $input->getOption("name");
        if ($name) {
            $this->hostnameDeterminer->set($name);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function configure() {
        parent::configure();
        $this->addOption("name", null, InputOption::VALUE_REQUIRED, "Hostname");
    }
}
