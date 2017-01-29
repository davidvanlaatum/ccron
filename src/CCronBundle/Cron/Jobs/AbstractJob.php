<?php
namespace CCronBundle\Cron\Jobs;

use CCronBundle\Entity\Job as JobEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractJob implements Job {
    protected $id;
    protected $name;

    public static function factory(JobEntity $job) {
        $class = __NAMESPACE__ . '\\' . $job->getType();
        if (!class_exists($class)) {
            throw new \Exception("No such job type $class");
        }
        if (!method_exists($class, "build")) {
            throw new \Exception("No build method on $class!");
        }
        return $class::build($job);
    }

    public static function fillJob(Job $command, JobEntity $job) {
        if ($command instanceof AbstractJob) {
            $command->setId($job->getId());
            $command->setName($job->getName());
        }
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }
}
