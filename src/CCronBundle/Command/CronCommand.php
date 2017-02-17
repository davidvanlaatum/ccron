<?php

namespace CCronBundle\Command;

use CCronBundle\Cron\HostnameDeterminer;
use CCronBundle\Cron\Runner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CronCommand extends DaemonCommand {
    /** @var Runner */
    protected $runner;

    public function __construct(HostnameDeterminer $hostnameDeterminer, Runner $runner) {
        parent::__construct($hostnameDeterminer);
        $this->runner = $runner;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure() {
        parent::configure();
        $this->setName("cron");
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->runner->run();
    }
}
