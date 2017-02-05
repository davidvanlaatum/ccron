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
        $output = new JobRunOutput();
        $data = [
            'output' => 'ABC123'
        ];
        self::assertGettersAndSetters($output, $this->em, $data);
    }
}
