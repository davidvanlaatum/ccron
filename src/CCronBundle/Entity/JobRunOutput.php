<?php
namespace CCronBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="output")
 */
class JobRunOutput {

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="JobRun", mappedBy="output")
     * @var JobRun
     */
    protected $run;

    /**
     * @ORM\Column(type="text",nullable=true)
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
     * @return JobRun
     */
    public function getRun() {
        return $this->run;
    }

    /**
     * @param JobRun $run
     */
    public function setRun($run) {
        $this->run = $run;
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
