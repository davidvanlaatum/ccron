<?php

namespace CCronBundle\Entity;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class JobTest extends KernelTestCase {

    /** @var EntityManager */
    protected $em;

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        self::bootKernel();
    }

    public function testUpdatesNextRunCorrectly() {
        $job = new Job();
        $job->setName("A Test Job");
        $job->setCronSchedule("* * * * *");
        $job->setCommand("echo hi");
        $this->em->persist($job);
        $this->em->flush($job);
        self::assertNotNull($job->getNextRun());
        $previous = $job->getNextRun();
        $job->setCronSchedule("@daily");
        $this->em->persist($job);
        $this->em->flush($job);
        self::assertNotNull($job->getNextRun());
        self::assertNotEquals($previous, $job->getNextRun());
    }

    protected function setUp() {
        parent::setUp();
        $this->em = static::$kernel->getContainer()->get("doctrine.orm.default_entity_manager");
        $this->em->beginTransaction();
    }

    protected function tearDown() {
        $this->em->rollback();
        parent::tearDown();
    }
}
