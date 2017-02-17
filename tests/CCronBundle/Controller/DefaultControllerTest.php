<?php

namespace Tests\AppBundle\Controller;

use CCronBundle\Entity\Job;
use CCronBundle\Entity\JobRun;
use CCronBundle\Entity\JobRunOutput;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Tests\CCronBundle\ContainerAwareTestCase;
use Tests\CCronBundle\DBAwareTestTrait;
use Tests\CCronBundle\WebTestTrait;

class DefaultControllerTest extends WebTestCase {
    use ContainerAwareTestCase;
    use DBAwareTestTrait;
    use WebTestTrait;

    /**
     * @covers \CCronBundle\Controller\DefaultController::indexAction
     */
    public function testIndex() {
        $client = static::createClient();
        list($job1, $job2, $builds) = $this->generateJobData();
        $this->logIn($client);
        $crawler = $client->request('GET', self::router()->generate('homepage'));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $jobInfo = $crawler->filter('[job-id=' . $job1->getID() . ']');
        $this->checkJob($jobInfo, $job1);
        $this->assertEquals('1h1m5s', trim($jobInfo->filter(".job-last-run-interval")->text()));

        $jobInfo = $crawler->filter('[job-id=' . $job2->getID() . ']');
        $this->checkJob($jobInfo, $job2);
        $this->assertEquals('', trim($jobInfo->filter(".job-last-run-interval")->text()));
        $this->assertRecentBuilds($builds, $crawler);
    }

    /**
     * @param int $buildCount
     * @return array
     */
    protected function generateJobData($buildCount = 1) {
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
        for ($i = 0; $i < $buildCount; $i++) {
            $jobRunOutput = new JobRunOutput();
            $jobRunOutput->setOutput('Hi all');
            $em->persist($jobRunOutput);

            $builds[$i] = new JobRun();
            $builds[$i]->setJob($job1);
            $builds[$i]->setTime($job1->getLastRun());
            $builds[$i]->setRunTime($job1->getLastRunTime());
            $builds[$i]->setHost('Host');
            $builds[$i]->setOutput($jobRunOutput);
            $em->persist($builds[$i]);
        }

        $em->flush();
        return [$job1, $job2, $builds];
    }

    protected function checkJob(Crawler $jobInfo, Job $job) {
        $this->assertGreaterThan(0, $jobInfo->count());
        $this->assertEquals($job->getName(), trim($jobInfo->filter(".job-name")->text()));
        $this->assertEquals($job->getCronSchedule(), trim($jobInfo->filter(".job-schedule")->text()));
        $this->assertEquals($job->getLastRun(), $this->stringToDate($jobInfo->filter(".job-last-run")->text()));
        $this->assertEquals($job->getNextRun(), $this->stringToDate($jobInfo->filter(".job-next-run")->text()));
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
     * @covers \CCronBundle\Controller\DefaultController::recentBuildsAction
     */
    public function testRecentBuilds() {
        $client = static::createClient();
        list($job1, $job2, $builds) = $this->generateJobData();
        $this->logIn($client);
        $crawler = $client->request('GET', self::router()->generate('builds_recent'));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRecentBuilds($builds, $crawler);
    }

    /**
     * @covers \CCronBundle\Controller\BuildController::viewConsoleAction()
     */
    public function testConsole() {
        $client = static::createClient();
        /**
         * @var JobRun[] $builds
         * @var Job $job1
         * @var Job $job2
         */
        list($job1, $job2, $builds) = $this->generateJobData();
        $this->logIn($client);
        $crawler = $client->request('GET', self::router()->generate('viewconsole', ['job' => $job1->getId(), 'id' => $builds[0]->getId()]));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isCacheable(), 'Response is not cacheable');
        $this->assertEquals('Hi all', $client->getResponse()->getContent());
        $this->assertEquals('text/plain; charset=UTF-8', $client->getResponse()->headers->get('Content-Type'));

        $client->request('GET', sprintf('/job/%d/console/%s', $job2->getId(), $builds[0]->getId()));
        $this->assertTrue($client->getResponse()->isNotFound());

        $client->request('GET', sprintf('/job/%d/console/%s', $job2->getId(), 0));
        $this->assertTrue($client->getResponse()->isNotFound());
    }

    /**
     * @covers \CCronBundle\Controller\JobController::addJobAction()
     */
    public function testAddJob() {
        $client = static::createClient();
        $this->logIn($client);
        $crawler = $client->request('GET', self::router()->generate('addjob'));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEquals(0, $crawler->selectButton('Delete')->count());
        $form = $crawler->selectButton('job_form[save]')->form([
            'job_form[name]' => 'Hi all',
            'job_form[cronSchedule]' => '@daily',
            'job_form[command]' => 'echo hi all'
        ]);
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/'));

        /** @var Job $job */
        $job = $client->getContainer()->get('doctrine.orm.default_entity_manager')->createQuery("SELECT j FROM " . Job::class . " j WHERE j.name = 'Hi all'")
            ->getSingleResult();
        $this->assertNotNull($job);
        $this->assertEquals('Hi all', $job->getName());
        $this->assertEquals('@daily', $job->getCronSchedule());
        $this->assertEquals('echo hi all', $job->getCommand());
    }

    /**
     * @covers \CCronBundle\Controller\JobController::editJobAction()
     */
    public function testEditJob() {
        $client = static::createClient();
        $this->logIn($client);
        $job = new Job();
        $job->setName('Hi all');
        $job->setCronSchedule('@daily');
        $job->setCommand('echo hi all');
        $em = $client->getContainer()->get('doctrine.orm.default_entity_manager');
        $em->persist($job);
        $em->flush($job);
        $this->assertNotNull($job->getId());

        $crawler = $client->request('GET', self::router()->generate('editjob', ['id' => $job->getId()]));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEquals(1, $crawler->selectButton('Delete')->count());
        $this->assertEquals(1, $crawler->selectButton('Save')->count());

        $form = $crawler->selectButton('job_form[save]')->form();
        self::assertEquals('Hi all', $form->get('job_form[name]')->getValue());
        self::assertEquals('@daily', $form->get('job_form[cronSchedule]')->getValue());
        self::assertEquals('echo hi all', $form->get('job_form[command]')->getValue());

        $form->get('job_form[command]')->setValue('');

        $crawler = $client->submit($form);
        self::assertTrue($client->getResponse()->isSuccessful());

        $job2 = $em->find(Job::class, $job->getId());
        $this->assertEquals($job->getCommand(), $job2->getCommand());

        $form = $crawler->selectButton('job_form[save]')->form();
        $form->get('job_form[command]')->setValue($job->getCommand());
        $client->submit($form);
        self::assertTrue($client->getResponse()->isRedirect('/'));

        $crawler = $client->request('GET', sprintf('/job/%d/edit', $job->getId()));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEquals(1, $crawler->selectButton('Delete')->count());
        $form = $crawler->selectButton('job_form[delete]')->form();
        $client->submit($form, ['job_form[delete]' => 'Delete']);
        self::assertTrue($client->getResponse()->isRedirect('/'));
        $em->clear();
        $job2 = $em->find(Job::class, $job->getId());
        self::assertNull($job2);
    }

    /**
     * @covers \CCronBundle\Controller\BuildController::viewBuildsAction()
     */
    public function testViewBuilds() {
        /** @var Job $job */
        /** @var JobRun[] $builds */
        list($job, $job2, $builds) = $this->generateJobData(10);

        $client = static::createClient();
        $this->logIn($client);
        $crawler = $client->request('GET', self::router()->generate('viewbuilds', ['id' => $job->getId()]));
        $this->assertTrue($client->getResponse()->isSuccessful());

        $buildRow = $crawler->filter('[build-id=' . $builds[0]->getId() . ']');
        self::assertEquals($builds[0]->getId(), $buildRow->filter('.build-id')->text());
        self::assertEquals($builds[0]->getTime(), $this->stringToDate($buildRow->filter('.build-time')->text()));
        self::assertEquals('1h1m5s', $buildRow->filter('.build-interval')->text());
        self::assertEquals($builds[0]->getHost(), $buildRow->filter('.build-host')->text());
    }

}
