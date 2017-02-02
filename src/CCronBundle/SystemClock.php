<?php
namespace CCronBundle;

class SystemClock implements Clock {

    function getCurrentDateTime() {
        return new \DateTime();
    }

    /** @return int */
    function getTime() {
        return time();
    }

    /** @return float */
    function getTimeOfDay() {
        return gettimeofday(true);
    }
}
