<?php
namespace Tests\CCronBundle\Entity;

use CCronBundle\Entity\Job;
use CCronBundle\Entity\JobRun;
use CCronBundle\Entity\JobRunOutput;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\CCronBundle\Assert\UnitTestHelpers;
use Tests\CCronBundle\ContainerAwareTestCase;
use Tests\CCronBundle\DBAwareTestTrait;

class JobRunOutputTest extends KernelTestCase {
    use ContainerAwareTestCase;
    use DBAwareTestTrait;
    use UnitTestHelpers;

    public function testSettersAndGetters() {
        $job = new Job();
        $jobRun = new JobRun();
        $job->setName('A Test Job');
        $job->setCronSchedule('* * * * *');
        $job->setCommand('echo hi');
        $jobRun->setJob($job);
        $jobRun->setTime(new \DateTime());
        $jobRun->setHost('Host');
        $jobRun->setRunTime(1);
        $this->em->persist($job);
        $this->em->persist($jobRun);
        $output = new JobRunOutput();
        $data = [
            'output' => 'ABC123'
        ];
        self::assertGettersAndSetters($output, $this->em, $data);
    }
}
