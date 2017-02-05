<?php

namespace CCronBundle\Repository;

use CCronBundle\Entity\Job;
use CCronBundle\Entity\JobRun;
use Doctrine\ORM\EntityRepository;

class JobRunRepository extends EntityRepository {
    /**
     * @param int $limit
     * @return JobRun[]
     */
    public function getRecent($limit = 25) {
        $query = $this->getEntityManager()->getRepository(JobRun::class)->createNamedQuery("recent.builds");
        $query->setMaxResults($limit);
        return $query->getResult();
    }

    public function forJob(Job $job) {
        $query = $this->getEntityManager()->getRepository(JobRun::class)->createNamedQuery("findForJob");
        return $query->execute(["job" => $job]);
    }
}
