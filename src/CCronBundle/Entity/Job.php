<?php
namespace CCronBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\EntityListeners({"CCronBundle\EventListener\JobPersistListener"})
 * @ORM\Table(name="jobs",uniqueConstraints={@ORM\UniqueConstraint(name="job_name", columns={"name"})})
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
     * @\CCronBundle\Validator\Constraints\Cron
     */
    protected $cronSchedule;

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
     * @ORM\OneToMany(targetEntity="JobRun",mappedBy="job",cascade={"remove"},fetch="LAZY")
     */
    protected $runs;

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
    public function getCronSchedule() {
        return $this->cronSchedule;
    }

    /**
     * @param string $cronSchedule
     */
    public function setCronSchedule($cronSchedule) {
        $this->cronSchedule = $cronSchedule;
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

    /**
     * @param int $lastRunTime
     */
    public function setLastRunTime($lastRunTime) {
        $this->lastRunTime = $lastRunTime;
    }

    public function getLastRunTimeInterval() {
        if ($this->lastRunTime > 0) {
            return new \DateInterval('PT' . $this->lastRunTime . 'S');
        }
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

    /**
     * @return mixed
     */
    public function getRuns() {
        return $this->runs;
    }

    /**
     * @param mixed $runs
     */
    public function setRuns($runs) {
        $this->runs = $runs;
    }
}