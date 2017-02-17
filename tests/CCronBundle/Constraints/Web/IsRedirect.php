<?php
namespace Tests\CCronBundle\Constraints\Web;


use Symfony\Component\HttpFoundation\Response;
use Tests\CCronBundle\WebTestTrait;

class IsRedirect extends AbstractWebResponseConstraint {

    /** @var \PHPUnit_Framework_Constraint */
    protected $location;

    /**
     * IsRedirect constructor.
     * @param $location
     */
    public function __construct($location) {
        parent::__construct();
        if ($location instanceof \PHPUnit_Framework_Constraint) {
            $this->location = $location;
        } else {
            $this->location = new \PHPUnit_Framework_Constraint_IsEqual(WebTestTrait::fixURL($location));
        }
    }


    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function toString() {
        return "Response with redirect " . $this->location->toString();
    }

    protected function responseMatches(Response $other, &$failureDescription = null) {
        $location = WebTestTrait::fixURL($other->headers->get("Location"));
        $rt = $other->isRedirect() && $this->location->evaluate($location, '', true);

        if (!$rt && $failureDescription !== null) {
            if (!$other->isRedirect()) {
                $failureDescription[] = sprintf("Return code %d is not a redirection", $other->getStatusCode());
            } else {
                $failureDescription[] = sprintf("location value %s", $this->location->failureDescription($location));
            }
        }

        return $rt;
    }
}
