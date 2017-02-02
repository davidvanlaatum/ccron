<?php

namespace CCronBundle;

interface Clock {
    /** @return \DateTime */
    function getCurrentDateTime();

    /** @return int */
    function getTime();

    /** @return float */
    function getTimeOfDay();
}
