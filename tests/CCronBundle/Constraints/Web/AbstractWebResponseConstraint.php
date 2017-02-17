<?php

namespace Tests\CCronBundle\Constraints\Web;

use Symfony\Component\HttpFoundation\Response;

abstract class AbstractWebResponseConstraint extends \PHPUnit_Framework_Constraint {

    /**
     * {@inheritDoc}
     */
    protected final function matches($other) {
        if ($other instanceof Response) {
            return $this->responseMatches($other);
        } else {
            return false;
        }
    }

    protected abstract function responseMatches(Response $other, &$failureDescription = null);

    /**
     * {@inheritDoc}
     */
    protected final function failureDescription($other) {
        if ($other instanceof Response) {
            $description = [];
            $this->responseMatches($other, $description);
            return array_pop($description);
        } else {
            return false;
        }
    }
}
