<?php
namespace CCronBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="CCronBundle\Repository\JobRunRepository")
 * @ORM\Table(name="runs")
 * @ORM\NamedQueries(value = {
 *     @ORM\NamedQuery(name="findForJob",query="SELECT j FROM __CLASS__ j WHERE j.job = :job"),
 *     @ORM\NamedQuery(name="recent.builds",query="SELECT j FROM __CLASS__ j ORDER BY j.time DESC"),
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
     * @ORM\ManyToOne(targetEntity="Job",inversedBy="runs")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $job;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $time;

    /**
     * @ORM\OneToOne(targetEntity="JobRunOutput", cascade={"persist","remove"}, fetch = "EXTRA_LAZY")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $output;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $runTime;

    /**
     * @ORM\Column(type="string", length=64)
     * @var string
     */
    protected $host;

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return Job
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
     * @return JobRunOutput
     */
    public function getOutput() {
        return $this->output;
    }

    /**
     * @param JobRunOutput $output
     */
    public function setOutput(JobRunOutput $output) {
        $this->output = $output;
    }

    /**
     * @return int
     */
    public function getRunTime() {
        return $this->runTime;
    }

    /**
     * @param int $runTime
     */
    public function setRunTime($runTime) {
        $this->runTime = $runTime;
    }

    public function getRunTimeInterval() {
        if ($this->runTime > 0) {
            if ($this->time) {
                $t1 = $this->time;
            } else {
                $t1 = new \DateTime();
            }
            $t2 = clone $t1;
            $t2->add(new \DateInterval('PT' . $this->runTime . 'S'));
            return $t1->diff($t2);
        }
        return null;
    }

    /**
     * @return string
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost($host) {
        $this->host = $host;
    }
}
