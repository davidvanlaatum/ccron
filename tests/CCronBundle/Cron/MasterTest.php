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

    /**
     * @covers \CCronBundle\Cron\Master::checkForWork
     * @covers \CCronBundle\Cron\Master::setEntityManager
     * @covers \CCronBundle\Cron\Master::setJobQueuer
     * @covers \CCronBundle\Cron\Master::setLogger
     */
    public function testCheckForWork() {
        $clock = new MockClock();
        $job = new Job();
        $job->setCronSchedule('@daily');
        $job->setNextRun($clock->getCurrentDateTime());
        $nextTime = new \DateTime('1970-01-02');

        /** @var JobRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->createMock(JobRepository::class);
        $repo->expects($this->exactly(2))->method('getWork')->willReturnOnConsecutiveCalls([$job], []);

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->exactly(2))->method("transactional")->willReturnCallback(function ($function) use ($em) {
            return $function($em);
        });
        $em->expects($this->any())->method('getRepository')->with(Job::class)->willReturn($repo);

        /** @var JobQueuer|\PHPUnit_Framework_MockObject_MockObject $jobQueuer */
        $jobQueuer = $this->createMock(JobQueuer::class);
        $jobQueuer->expects($this->once())->method('runJob')->with($job, $nextTime);

        $em->expects($this->once())->method('persist')->with($job);

        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $master = new Master($clock);
        $master->setEntityManager($em);
        $master->setJobQueuer($jobQueuer);
        $master->setLogger($logger);
        $master->checkForWork();

        $this->assertEquals($nextTime, $job->getNextRun());
        $master->checkForWork();
    }

    /**
     * @covers \CCronBundle\Cron\Master::scheduleWork
     * @covers \CCronBundle\Cron\Master::__construct
     * @covers \CCronBundle\Cron\Master::setHostnameDeterminer
     * @covers \CCronBundle\Cron\Master::setFailoverTracker
     */
    public function testScheduleWork() {
        $clock = new MockClock();
        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        /** @var JobRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->createMock(JobRepository::class);

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->exactly(2))->method("transactional")->willReturnCallback(function ($function) use ($em) {
            return $function($em);
        });

        /** @var FailoverTracker|\PHPUnit_Framework_MockObject_MockObject $failoverTracker */
        $failoverTracker = $this->createMock(FailoverTracker::class);
        $failoverTracker->method('getUptime')->willReturn(10);
        $failoverTracker->method('getMasterUptime')->willReturn(1);

        $master = new Master($clock);
        $master->setLogger($logger);
        $master->setEntityManager($em);
        $master->setHostnameDeterminer(new HostnameDeterminer());
        $master->setFailoverTracker($failoverTracker);
        $master->scheduleWork();
        $clock->setCurrentTime(10);
        $em->expects($this->any())->method('getRepository')->with(Job::class)->willReturn($repo);
        $repo->expects($this->once())->method('getWork')->willReturn([]);
        $master->scheduleWork();
    }

    /**
     * @covers \CCronBundle\Cron\Master::run
     * @covers \CCronBundle\Cron\Master::setRPCServer
     * @covers \CCronBundle\Cron\Master::setKeepaliveConsumer
     * @covers \CCronBundle\Cron\Master::setMasterConsumer
     * @covers \CCronBundle\Cron\Master::setRunning
     */
    public function testRunNotMaster() {
        $clock = new MockClock();

        /** @var Consumer|\PHPUnit_Framework_MockObject_MockObject $keepAliveConsumer */
        $keepAliveConsumer = $this->createMock(Consumer::class);

        /** @var Consumer|\PHPUnit_Framework_MockObject_MockObject $rpcServer */
        $rpcServer = $this->createMock(Consumer::class);

        /** @var MultiConsumer|\PHPUnit_Framework_MockObject_MockObject $masterConsumer */
        $masterConsumer = $this->createMock(MultiConsumer::class);
        $masterConsumer->expects($this->once())->method('addSubQueue')->with($keepAliveConsumer);
        $masterConsumer->expects($this->exactly(2))->method('removeSubQueue')->with($rpcServer);

        /** @var FailoverTracker|\PHPUnit_Framework_MockObject_MockObject $failoverTracker */
        $failoverTracker = $this->createMock(FailoverTracker::class);
        $failoverTracker->method('getUptime')->willReturn(10);
        $failoverTracker->method('getMasterUptime')->willReturn(1);

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->atLeastOnce())->method('clear');

        /** @var Running|\PHPUnit_Framework_MockObject_MockObject $running */
        $running = $this->createMock(Running::class);
        $running->expects($this->exactly(3))->method('isRunning')->willReturnOnConsecutiveCalls(true, true, false);

        $master = new Master($clock);
        $master->setMasterConsumer($masterConsumer);
        $master->setKeepaliveConsumer($keepAliveConsumer);
        $master->setRunning($running);
        $master->setFailoverTracker($failoverTracker);
        $master->setRPCServer($rpcServer);
        $master->setEntityManager($em);
        $master->run();
    }

    /**
     * @covers \CCronBundle\Cron\Master::run
     */
    public function testRunMaster() {
        $clock = new MockClock();

        /** @var Consumer|\PHPUnit_Framework_MockObject_MockObject $keepAliveConsumer */
        $keepAliveConsumer = $this->createMock(Consumer::class);

        /** @var Consumer|\PHPUnit_Framework_MockObject_MockObject $rpcServer */
        $rpcServer = $this->createMock(Consumer::class);

        /** @var MultiConsumer|\PHPUnit_Framework_MockObject_MockObject $masterConsumer */
        $masterConsumer = $this->createMock(MultiConsumer::class);
        $masterConsumer->expects($this->exactly(3))->method('addSubQueue')->withConsecutive($keepAliveConsumer, $rpcServer, $rpcServer);

        /** @var FailoverTracker|\PHPUnit_Framework_MockObject_MockObject $failoverTracker */
        $failoverTracker = $this->createMock(FailoverTracker::class);
        $failoverTracker->method('getUptime')->willReturn(10);
        $failoverTracker->method('getMasterUptime')->willReturn(1);
        $failoverTracker->method('isMaster')->willReturn(true);

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->atLeastOnce())->method('clear');

        /** @var Running|\PHPUnit_Framework_MockObject_MockObject $running */
        $running = $this->createMock(Running::class);
        $running->expects($this->exactly(3))->method('isRunning')->willReturnOnConsecutiveCalls(true, true, false);

        $master = new Master($clock);
        $master->setMasterConsumer($masterConsumer);
        $master->setKeepaliveConsumer($keepAliveConsumer);
        $master->setRunning($running);
        $master->setFailoverTracker($failoverTracker);
        $master->setRPCServer($rpcServer);
        $master->setEntityManager($em);
        $master->run();
    }

    /**
     * @covers \CCronBundle\Cron\Master::updateStats
     */
    public function testUpdateStats() {
        $clock = new MockClock();
        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManager::class);
        $em->method("transactional")->willReturnCallback(function ($function) use ($em) {
            return $function($em);
        });
        $em->expects($this->once())->method('persist')->with($this->callback(function (CurrentState $state) use ($clock) {
            $this->assertEquals(1, $state->getId());
            $this->assertEquals(1, $state->getMasterUptime());
            $this->assertEquals(10, $state->getUptime());
            $this->assertEquals($clock->getCurrentDateTime(), $state->getLastUpdated());
            return true;
        }));

        /** @var FailoverTracker|\PHPUnit_Framework_MockObject_MockObject $failoverTracker */
        $failoverTracker = $this->createMock(FailoverTracker::class);
        $failoverTracker->method('getUptime')->willReturn(10);
        $failoverTracker->method('getMasterUptime')->willReturn(1);
        $failoverTracker->method('isMaster')->willReturn(true);

        $master = new Master($clock);
        $master->setFailoverTracker($failoverTracker);
        $master->setEntityManager($em);
        $master->setLogger($logger);
        $master->setHostnameDeterminer(new HostnameDeterminer());
        $master->updateStats();
    }
}
