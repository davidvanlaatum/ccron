<?php

namespace CCronBundle\Command;

use CCronBundle\Cron\Runner;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CronCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName("cron");
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        /** @var Runner $runner */
        $runner = null;
        while ($runner == null) {
            try {
                $runner = $this->getContainer()->get("cronrunner");
            } catch (\ErrorException $e) {
                sleep(2);
            }
        }
        $runner->run();
    }
}
