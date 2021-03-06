<?php
namespace CCronBundle\EventListener;

use Doctrine\ORM\Mapping as ORM;
use CCronBundle\Entity\Job;
use Cron\CronExpression;
use Doctrine\ORM\Event\LifecycleEventArgs;

class JobPersistListener {
    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     * @param Job $job
     * @param LifecycleEventArgs $args
     */
    public function prePersist(Job $job, LifecycleEventArgs $args) {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $cs = $uow->getEntityChangeSet($job);
        if ((isset($cs['cronSchedule']) && !isset($cs['nextRun'])) || ($job->getCronSchedule() && !$job->getNextRun())) {
            $job->setNextRun(CronExpression::factory($job->getCronSchedule())->getNextRunDate());
        }
    }
}
