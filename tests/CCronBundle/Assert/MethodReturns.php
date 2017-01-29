<?php
/**
 * Created by IntelliJ IDEA.
 * User: david
 * Date: 29/1/17
 * Time: 3:40 PM
 */

namespace Tests\CCronBundle\Assert;

class MethodReturns extends \PHPUnit_Framework_Constraint {
    protected $method;
    protected $constraint;

    /**
     * MethodReturns constructor.
     * @param $method
     * @param $constraint
     */
    public function __construct($method, $constraint) {
        parent::__construct();
        $this->method = $method;
        $this->constraint = $constraint;
        if (!($this->constraint instanceof \PHPUnit_Framework_Constraint)) {
            $this->constraint = new \PHPUnit_Framework_Constraint_IsEqual($constraint);
        }
    }

    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function toString() {
        return sprintf("method %s return %s", $this->method, $this->constraint->toString());
    }

    protected function matches($other) {
        try {
            $method = new \ReflectionMethod($other, $this->method);
            if (!$method->isPublic() || $method->isStatic()) {
                return false;
            } else {
                $value = $method->invoke($other);
                return $this->constraint->evaluate($value, '', true);
            }
        } catch (\ReflectionException $e) {
            return false;
        }
    }

    protected function failureDescription($other) {
        $description = null;
        try {
            $method = new \ReflectionMethod($other, $this->method);
            if (!$method->isPublic()) {
                $description = $this->getMethodText($other) . ' not public';
            } else if ($method->isStatic()) {
                $description = $this->getMethodText($other) . ' is static';
            } else {
                try {
                    $value = $method->invoke($other);
                    $description = 'method ' . $this->getMethodText($other) . ' return ' . $this->constraint->failureDescription($value);
                } catch (\ReflectionException $e) {
                    $description = $e->getMessage();
                }
            }
        } catch (\ReflectionException $e) {
            $description = $e->getMessage();
        }
        return $description;
    }

    protected function getMethodText($other) {
        return get_class($other) . "::" . $this->method;
    }
}
