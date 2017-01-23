<?php
namespace CCronBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="state")
 */
class CurrentState {
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $master;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    protected $lastUpdated;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    protected $uptime;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    protected $masterUptime;

    /**
     * CurrentState constructor.
     * @param $id
     */
    public function __construct($id = null) {
        $this->id = $id;
    }


    /**
     * @return string
     */
    public function getMaster() {
        return $this->master;
    }

    /**
     * @param string $master
     */
    public function setMaster($master) {
        $this->master = $master;
    }

    /**
     * @return \DateTime
     */
    public function getLastUpdated() {
        return $this->lastUpdated;
    }

    /**
     * @param \DateTime $lastUpdated
     */
    public function setLastUpdated($lastUpdated) {
        $this->lastUpdated = $lastUpdated;
    }

    /**
     * @param float $uptime
     */
    public function setUptime($uptime) {
        $this->uptime = $uptime;
    }

    /**
     * @return float
     */
    public function getUptime() {
        return $this->uptime;
    }

    /**
     * @param float $masterUptime
     */
    public function setMasterUptime($masterUptime) {
        $this->masterUptime = $masterUptime;
    }

    /**
     * @return float
     */
    public function getMasterUptime() {
        return $this->masterUptime;
    }

}
