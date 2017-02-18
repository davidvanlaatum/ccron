<?php

namespace Tests\CCronBundle\Twig;

use CCronBundle\MockClock;
use CCronBundle\Twig\RuntimeFormatter;
use Text_Template;

class IntegrationTest extends \Twig_Test_IntegrationTestCase {
    public function getExtensions() {
        $clock = new MockClock();
        $clock->setCurrentTime(2);

        return array(
            new RuntimeFormatter($clock),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getFixturesDir() {
        return dirname(__FILE__) . '/Fixtures/';
    }
}
