<?php
namespace Tests\CCronBundle\Assert;

class AlwaysFailConstraint extends \PHPUnit_Framework_Constraint {
    protected $message;

    /**
     * AlwaysFailConstraint constructor.
     */
    public function __construct($message) {
        parent::__construct();
        $this->message = $message;
    }

    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function toString() {
        return $this->message;
    }

    protected function failureDescription($other) {
        return $this->message;
    }
}
