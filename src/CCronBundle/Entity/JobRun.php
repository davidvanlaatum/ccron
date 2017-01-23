<?php
namespace CCronBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="runs")
 * @ORM\NamedQueries(value = {
 *     @ORM\NamedQuery(name="findForJob",query="SELECT j FROM __CLASS__ j WHERE j.job = :job"),
 * })
 */
class JobRun {

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Job")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $job;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $time;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    protected $output;

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getJob() {
        return $this->job;
    }

    /**
     * @param Job $job
     */
    public function setJob($job) {
        $this->job = $job;
    }

    /**
     * @return \DateTime
     */
    public function getTime() {
        return $this->time;
    }

    /**
     * @param \DateTime $time
     */
    public function setTime($time) {
        $this->time = $time;
    }

    /**
     * @return string
     */
    public function getOutput() {
        return $this->output;
    }

    /**
     * @param string $output
     */
    public function setOutput($output) {
        $this->output = $output;
    }
}
