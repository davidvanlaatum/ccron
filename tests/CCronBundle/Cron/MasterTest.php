<?php

namespace Tests\CCronBundle\Cron;

use CCronBundle\Cron\FailoverTracker;
use CCronBundle\Cron\HostnameDeterminer;
use CCronBundle\Cron\JobQueuer;
use CCronBundle\Cron\Master;
use CCronBundle\Cron\MultiConsumer;
use CCronBundle\Cron\Running;
use CCronBundle\Entity\CurrentState;
use CCronBundle\Entity\Job;
use CCronBundle\MockClock;
use CCronBundle\Repository\JobRepository;
use Doctrine\ORM\EntityManager;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use Psr\Log\LoggerInterface;

class MasterTest extends \PHPUnit_Framework_TestCase {
    /** @var FailoverTracker|\PHPUnit_Framework_MockObject_MockObject */
    protected $failoverTracker;
    /** @var Consumer|\PHPUnit_Framework_MockObject_MockObject */
    protected $keepAliveConsumer;
    /** @var Consumer|\PHPUnit_Framework_MockObject_MockObject */
    protected $rpcServer;
    /** @var Master */
    protected $master;
    /** @var MockClock */
    protected $clock;
    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $em;
    /** @var Running|\PHPUnit_Framework_MockObject_MockObject */
    protected $running;
    /** @var HostnameDeterminer */
    protected $hostnameDeterminer;
    /** @var MultiConsumer|\PHPUnit_Framework_MockObject_MockObject */
    protected $masterConsumer;
    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $logger;
    /** @var JobQueuer|\PHPUnit_Framework_MockObject_MockObject */
    protected $jobQueuer;

    /**
     * @covers \CCronBundle\Cron\Master::checkForWork
     * @covers \CCronBundle\Cron\Master::setEntityManager
     * @covers \CCronBundle\Cron\Master::setJobQueuer
     * @covers \CCronBundle\Cron\Master::setLogger
     */
    public function testCheckForWork() {
        $this->createMaster();
        $job = new Job();
        $job->setCronSchedule('@daily');
        $job->setNextRun($this->clock->getCurrentDateTime());
        $nextTime = new \DateTime('1970-01-02');

        /** @var JobRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->createMock(JobRepository::class);
        $repo->expects($this->exactly(2))->method('getWork')->willReturnOnConsecutiveCalls([$job], []);
        $this->em->expects($this->any())->method('getRepository')->with(Job::class)->willReturn($repo);
        $this->em->expects($this->once())->method('persist')->with($job);
        $this->jobQueuer->expects($this->once())->method('runJob')->with($job, $nextTime);
        $this->master->checkForWork();
        $this->assertEquals($nextTime, $job->getNextRun());
        $this->master->checkForWork();
    }

    protected function createMaster() {
        $this->clock = new MockClock();
        $this->master = new Master($this->clock);
        $this->keepAliveConsumer = $this->createMock(Consumer::class);
        $this->rpcServer = $this->createMock(Consumer::class);

        $this->masterConsumer = $this->createMock(MultiConsumer::class);

        $this->failoverTracker = $this->createMock(FailoverTracker::class);
        $this->failoverTracker->method('getUptime')->willReturn(10);
        $this->failoverTracker->method('getMasterUptime')->willReturn(1);

        $this->em = $this->createMock(EntityManager::class);
        $this->em->method("transactional")->willReturnCallback(function ($function) {
            return $function($this->em);
        });

        $this->jobQueuer = $this->createMock(JobQueuer::class);

        $this->running = $this->createMock(Running::class);
        $this->running->method('isRunning')->willReturnOnConsecutiveCalls(true, true, false);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->master->setMasterConsumer($this->masterConsumer);
        $this->master->setKeepaliveConsumer($this->keepAliveConsumer);
        $this->master->setRunning($this->running);
        $this->master->setFailoverTracker($this->failoverTracker);
        $this->master->setRPCServer($this->rpcServer);
        $this->master->setEntityManager($this->em);
        $this->hostnameDeterminer = new HostnameDeterminer();
        $this->hostnameDeterminer->set("TestHost");
        $this->master->setHostnameDeterminer($this->hostnameDeterminer);
        $this->master->setLogger($this->logger);
        $this->master->setJobQueuer($this->jobQueuer);

    }

    /**
     * @covers \CCronBundle\Cron\Master::scheduleWork
     * @covers \CCronBundle\Cron\Master::__construct
     * @covers \CCronBundle\Cron\Master::setHostnameDeterminer
     * @covers \CCronBundle\Cron\Master::setFailoverTracker
     */
    public function testScheduleWork() {
        $this->createMaster();

        /** @var JobRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->createMock(JobRepository::class);

        $this->master->scheduleWork();
        $this->clock->setCurrentTime(10);
        $this->em->expects($this->any())->method('getRepository')->with(Job::class)->willReturn($repo);
        $repo->expects($this->once())->method('getWork')->willReturn([]);
        $this->master->scheduleWork();
    }

    /**
     * @covers \CCronBundle\Cron\Master::run
     * @covers \CCronBundle\Cron\Master::setRPCServer
     * @covers \CCronBundle\Cron\Master::setKeepaliveConsumer
     * @covers \CCronBundle\Cron\Master::setMasterConsumer
     * @covers \CCronBundle\Cron\Master::setRunning
     */
    public function testRunNotMaster() {
        $this->createMaster();
        $this->em->expects($this->atLeastOnce())->method('clear');
        $this->masterConsumer->expects($this->once())->method('addSubQueue')->with($this->keepAliveConsumer);
        $this->masterConsumer->expects($this->exactly(2))->method('removeSubQueue')->with($this->rpcServer);
        $this->master->run();
    }

    /**
     * @covers \CCronBundle\Cron\Master::run
     */
    public function testRunMaster() {
        $this->createMaster();
        $this->em->expects($this->atLeastOnce())->method('clear');
        $this->masterConsumer->expects($this->exactly(3))->method('addSubQueue')->withConsecutive($this->keepAliveConsumer, $this->rpcServer, $this->rpcServer);
        $this->failoverTracker->method('isMaster')->willReturn(true);
        $this->master->run();
    }

    /**
     * @covers \CCronBundle\Cron\Master::updateStats
     */
    public function testUpdateStats() {
        $this->createMaster();
        $this->em->expects($this->once())->method('persist')->with($this->callback(function (CurrentState $state) {
            $this->assertEquals(1, $state->getId());
            $this->assertEquals(1, $state->getMasterUptime());
            $this->assertEquals(10, $state->getUptime());
            $this->assertEquals($this->clock->getCurrentDateTime(), $state->getLastUpdated());
            return true;
        }));
        $this->failoverTracker->method('isMaster')->willReturn(true);
        $this->master->updateStats();
    }
}
