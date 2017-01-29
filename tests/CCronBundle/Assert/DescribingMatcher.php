<?php
namespace Tests\CCronBundle\Assert;


abstract class DescribingMatcher extends \PHPUnit_Framework_Constraint {
    protected $lastObject;
    protected $lastDescription;

    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function toString() {
        list($match, $description) = $this->matchesDescribing(null);
        return $description;
    }

    protected function matches($other) {
        try {
            $this->lastObject = $other;
            list($matches, $this->lastDescription) = $this->matchesDescribing($other);
            return $matches;
        } catch (\Exception $exception) {
            print $exception;
            throw $exception;
        }
    }

    /**
     * @param $other
     * @return array
     */
    abstract protected function matchesDescribing($other);

    protected function failureDescription($other) {
        if ($this->lastObject === $other) {
            return $this->lastDescription;
        } else {
            list($matches, $description) = $this->matchesDescribing($other);
            return $description;
        }
    }

}
