<?php

namespace Tests\AppBundle\Controller;

use CCronBundle\Entity\Job;
use CCronBundle\Entity\JobRun;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Tests\CCronBundle\ContainerAwareTestCase;
use Tests\CCronBundle\DBAwareTestTrait;

class DefaultControllerTest extends WebTestCase {
    use ContainerAwareTestCase;
    use DBAwareTestTrait;

    /**
     * @covers \CCronBundle\Controller\DefaultController::indexAction
     */
    public function testIndex() {
        $client = static::createClient();
        list($job1, $job2, $builds) = $this->generateJobData();
        $crawler = $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $jobInfo = $crawler->filter('[job-id=' . $job1->getID() . ']');
        $this->assertEquals($job1->getName(), trim($jobInfo->filter(".job-name")->text()));
        $this->assertEquals($job1->getCronSchedule(), trim($jobInfo->filter(".job-schedule")->text()));
        $this->assertEquals($job1->getLastRun(), $this->stringToDate($jobInfo->filter(".job-last-run")->text()));
        $this->assertEquals($job1->getNextRun(), $this->stringToDate($jobInfo->filter(".job-next-run")->text()));
        $this->assertEquals('1h1m5s', trim($jobInfo->filter(".job-last-run-interval")->text()));

        $jobInfo = $crawler->filter('[job-id=' . $job2->getID() . ']');
        $this->assertEquals($job2->getName(), trim($jobInfo->filter(".job-name")->text()));
        $this->assertEquals($job2->getCronSchedule(), trim($jobInfo->filter(".job-schedule")->text()));
        $this->assertEquals($job2->getLastRun(), $this->stringToDate($jobInfo->filter(".job-last-run")->text()));
        $this->assertEquals($job2->getNextRun(), $this->stringToDate($jobInfo->filter(".job-next-run")->text()));
        $this->assertEquals('', trim($jobInfo->filter(".job-last-run-interval")->text()));
        $this->assertRecentBuilds($builds, $crawler);
    }

    /**
     * @return array
     */
    protected function generateJobData() {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $job1 = new Job();
        $job1->setName("A Test Job");
        $job1->setCronSchedule('@daily');
        $job1->setCommand('echo hi all');
        $job1->setNextRun(new \DateTime('2000-01-06'));
        $job1->setLastRun(new \DateTime('2000-01-05 10:00:00'));
        $job1->setLastRunTime(3665);
        $job2 = new Job();
        $job2->setName("A Test Job 2");
        $job2->setCronSchedule('@daily');
        $job2->setCommand('echo hi all');
        $em->persist($job1);
        $em->persist($job2);

        $builds = [];
        $builds[0] = new JobRun();
        $builds[0]->setJob($job1);
        $builds[0]->setTime($job1->getLastRun());
        $builds[0]->setRunTime($job1->getLastRunTime());
        $builds[0]->setHost('Host');
        $em->persist($builds[0]);

        $em->flush();
        return [$job1, $job2, $builds];
    }

    public function stringToDate($string) {
        if (trim($string) == false) {
            return null;
        } else {
            return new \DateTime(trim($string));
        }
    }

    /**
     * @param JobRun[] $builds
     * @param Crawler $crawler
     */
    public function assertRecentBuilds($builds, Crawler $crawler) {
        foreach ($builds as $build) {
            $jobInfo = $crawler->filter('[build-id=' . $build->getID() . ']');
            $this->assertEquals($build->getHost(), trim($jobInfo->filter(".build-host")->text()));
            $this->assertEquals($build->getTime(), $this->stringToDate($jobInfo->filter(".build-time")->text()));
            $this->assertEquals('1h1m5s', trim($jobInfo->filter(".build-runtime")->text()));
        }
    }

    /**
     * @covers \CCronBundle\Controller\DefaultController::recentBuilds
     */
    public function testRecentBuilds() {
        $client = static::createClient();
        list($job1, $job2, $builds) = $this->generateJobData();
        $crawler = $client->request('GET', '/recentbuilds');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRecentBuilds($builds, $crawler);
    }
}
