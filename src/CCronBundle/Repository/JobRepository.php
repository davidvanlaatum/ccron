<?php

namespace CCronBundle\Repository;

use CCronBundle\Entity\Job;
use Doctrine\DBAL\LockMode;

class JobRepository extends \Doctrine\ORM\EntityRepository {
    /**
     * @param \DateTime $now
     * @return Job[]
     */
    public function getWork(\DateTime $now) {
        $query = $this->getEntityManager()->getRepository(Job::class)->createNamedQuery('poll.work');
        $query->setLockMode(LockMode::PESSIMISTIC_WRITE);
        return $query->execute(['now' => $now]);
    }
}
