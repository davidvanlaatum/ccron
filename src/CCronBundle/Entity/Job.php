<?php
namespace CCronBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="jobs")
 * @ORM\NamedQueries(value = {
 *     @ORM\NamedQuery(name="poll.work",query="SELECT j FROM __CLASS__ j WHERE j.nextRun IS NULL OR j.nextRun < :now"),
 *     @ORM\NamedQuery(name="jobs.all",query="SELECT j FROM __CLASS__ j")
 * })
 */
class Job {
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    /**
     * @ORM\Column(type="string", length=100)
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $cronschedule;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    protected $nextRun;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    protected $lastRun;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    protected $lastRunTime;

    /**
     * @ORM\Column(type="string", length=16)
     * @var string
     */
    protected $type = "Command";

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    protected $command;

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
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

    /**
     * @return string
     */
    public function getCronschedule() {
        return $this->cronschedule;
    }

    /**
     * @param string $cronschedule
     */
    public function setCronschedule($cronschedule) {
        $this->cronschedule = $cronschedule;
    }

    /**
     * @return \DateTime
     */
    public function getNextRun() {
        return $this->nextRun;
    }

    /**
     * @param \DateTime $nextRun
     */
    public function setNextRun($nextRun) {
        $this->nextRun = $nextRun;
    }

    /**
     * @return \DateTime
     */
    public function getLastRun() {
        return $this->lastRun;
    }

    /**
     * @param \DateTime $lastRun
     */
    public function setLastRun($lastRun) {
        $this->lastRun = $lastRun;
    }

    /**
     * @return int
     */
    public function getLastRunTime() {
        return $this->lastRunTime;
    }

    public function getLastRunTimeInterval() {
        return new \DateInterval('PT' . $this->lastRunTime . 'S');
    }

    /**
     * @param int $lastRunTime
     */
    public function setLastRunTime($lastRunTime) {
        $this->lastRunTime = $lastRunTime;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getCommand() {
        return $this->command;
    }

    /**
     * @param string $command
     */
    public function setCommand($command) {
        $this->command = $command;
    }
}
