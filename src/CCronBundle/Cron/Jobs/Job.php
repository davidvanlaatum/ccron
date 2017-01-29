<?php
namespace CCronBundle\Cron\Jobs;

use CCronBundle\Entity\JobRun;
use Symfony\Component\DependencyInjection\ContainerInterface;

interface Job {
    function execute();

    /**
     * @param int $id
     */
    public function setId($id);

    /**
     * @return int
     */
    public function getId();

    /**
     * @param string $name
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getName();

    public function fillInLog(JobRun $log);
}
