<?php

namespace Tests\CCronBundle\Entity;

use CCronBundle\Entity\Job;
use CCronBundle\Entity\JobRun;
use Tests\CCronBundle\ContainerAwareTestCase;
use Tests\CCronBundle\DBAwareTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\CCronBundle\Assert\UnitTestHelpers;

/**
 * @covers \CCronBundle\Entity\Job
 */
class JobTest extends KernelTestCase {
    use ContainerAwareTestCase;
    use DBAwareTestTrait;
    use UnitTestHelpers;

    /**
     * @covers \CCronBundle\EventListener\JobPersistListener
     */
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

    public function testSettersAndGetters() {
        $job = new Job();
        $data = [
            'name' => 'A Test Job',
            'cronSchedule' => '@daily',
            'nextRun' => null,
            'lastRun' => null,
            'lastRunTime' => 1,
            'type' => 'command',
            'command' => 'echo hi all'
        ];
        self::assertGettersAndSetters($job, $this->em, $data, function ($data) {
            $data['nextRun'] = ['expected' => self::isInstanceOf(\DateTime::class), 'value' => null];
            return $data;
        });
    }

    public function testGetLastRunTimeInterval() {
        $job = new Job();
        self::assertNull($job->getLastRunTimeInterval());
        $job->setLastRunTime(1);
        self::assertEquals(new \DateInterval('PT1S'), $job->getLastRunTimeInterval());
        $job->setLastRun(new \DateTime('2000-01-01'));
        $job->setLastRunTime(3665);
        self::assertEquals(new \DateInterval('PT1H1M5S'), $job->getLastRunTimeInterval());
    }

    public function testRuns() {
        $job = new Job();
        $job->setName("A Test Job");
        $job->setCronSchedule('@daily');
        $job->setCommand('echo hi all');
        $this->em->persist($job);
        $run = new JobRun();
        $run->setJob($job);
        $run->setTime(new \DateTime());
        $run->setRunTime(0);
        $run->setHost("Host");
        $this->em->persist($run);
        $this->em->flush();
        $this->em->clear();

        $job = $this->em->find(Job::class, $job->getId());
        self::assertCount(1, $job->getRuns());
    }
}
