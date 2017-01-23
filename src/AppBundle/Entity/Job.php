<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="jobs")
 * @ORM\NamedQueries(value = {
 *     @ORM\NamedQuery(name="poll.work",query="SELECT j FROM __CLASS__ j WHERE j.nextRun IS NULL OR j.nextRun < :now")
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
     */
    protected $nextRun;

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
