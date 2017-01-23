<?php
namespace AppBundle\Events;

abstract class AbstractEvent implements Event {
    protected $host;
    protected $uptime;

    /**
     * @return mixed
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * @param mixed $host
     */
    public function setHost($host) {
        $this->host = $host;
    }

    /**
     * @param int $uptime
     */
    public function setUptime($uptime) {
        $this->uptime = $uptime;
    }

    /**
     * @return int
     */
    public function getUptime() {
        return $this->uptime;
    }
}
