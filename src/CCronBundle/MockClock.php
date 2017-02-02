<?php
namespace CCronBundle;

class MockClock implements Clock {
    protected $currentTime;

    function getCurrentDateTime() {
        $now = new \DateTime();
        $now->setTimestamp($this->currentTime);
        return $now;
    }

    public function setCurrentTime($int) {
        $this->currentTime = $int;
    }

    /** @return int */
    function getTime() {
        return (int)$this->currentTime;
    }

    /** @return float */
    function getTimeOfDay() {
        return $this->currentTime;
    }
}
