<?php
namespace Tests\CCronBundle\Assert;


abstract class DescribingMatcher extends \PHPUnit_Framework_Constraint {
    protected $lastObject;
    protected $lastDescription;

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
