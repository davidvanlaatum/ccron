<?php
namespace Tests\CCronBundle\Assert;

class MethodAccepts extends DescribingMatcher {
    protected $method;
    protected $arguments;

    /**
     * MethodAccepts constructor.
     */
    public function __construct($method, $arguments) {
        parent::__construct();
        $this->method = $method;
        $this->arguments = $arguments;
    }


    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function toString() {
        $args = [];
        foreach ($this->arguments as $arg) {
            $args[] = $this->exporter->shortenedExport($arg);
        }
        return "method " . $this->method . "(" . implode(",", $args) . ") works";
    }

    /**
     * @param $other
     * @return array
     */
    protected function matchesDescribing($other) {
        $matches = false;
        $description = null;

        try {
            $reflection = new \ReflectionMethod($other, $this->method);
            if (!$reflection->isPublic()) {
                $description = $this->methodName($reflection) . " is not public";
            } else if ($reflection->isStatic()) {
                $description = $this->methodName($reflection) . " is not public";
            } else {
                $reflection->invokeArgs($other, $this->arguments);
                $matches = true;
            }
        } catch (\ReflectionException $exception) {
            $description = $exception->getMessage();
        }

        return [$matches, $description];
    }

    protected function methodName(\ReflectionMethod $reflection) {
        return $reflection->getDeclaringClass()->getName() . '::' . $reflection->getName();
    }
}
