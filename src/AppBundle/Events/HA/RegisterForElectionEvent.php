<?php
namespace AppBundle\Events\HA;

use AppBundle\Events\AbstractEvent;

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
