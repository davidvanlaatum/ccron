<?php

namespace Tests\CCronBundle\Entity;

use CCronBundle\Entity\Job;
use CCronBundle\Entity\JobRun;
use CCronBundle\Entity\JobRunOutput;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\CCronBundle\Assert\UnitTestHelpers;
use Tests\CCronBundle\ContainerAwareTestCase;
use Tests\CCronBundle\DBAwareTestTrait;

/**
 * @covers \CCronBundle\Entity\JobRun
 */
class JobRunTest extends KernelTestCase {
    use ContainerAwareTestCase;
    use DBAwareTestTrait;
    use UnitTestHelpers;

    public function testSettersAndGetters() {
        $jobRun = new JobRun();
        $job = new Job();
        $job->setName('A Test Job');
        $job->setCronSchedule('* * * * *');
        $job->setCommand('echo hi');
        $this->em->persist($job);
        $output = new JobRunOutput();
        $data = [
            'time' => new \DateTime(),
            'runTime' => 1,
            'host' => 'Host',
            'job' => $job,
            'output' => $output
        ];
        self::assertGettersAndSetters($jobRun, $this->em, $data);
    }

    public function testGetLastRunTimeInterval() {
        $job = new JobRun();
        self::assertNull($job->getRunTimeInterval());
        $job->setRunTime(1);
        self::assertEquals(new \DateInterval('PT1S'), $job->getRunTimeInterval());
        $job->setTime(new \DateTime('2000-01-01'));
        $job->setRunTime(3665);
        self::assertEquals(new \DateInterval('PT1H1M5S'), $job->getRunTimeInterval());
    }
}
