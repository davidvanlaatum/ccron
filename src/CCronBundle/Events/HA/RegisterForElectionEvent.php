<?php
namespace CCronBundle\Events\HA;

use CCronBundle\Events\AbstractEvent;

class RegisterForElectionEvent extends AbstractEvent {
    protected $random;

    /**
     * RegisterForElectionEvent constructor.
     */
    public function __construct() {
        $this->random = rand(0, 1000);
    }


    /**
     * @return mixed
     */
    public function getRandom() {
        return $this->random;
    }
}
