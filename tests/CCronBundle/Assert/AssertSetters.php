<?php
namespace Tests\CCronBundle\Assert;

use Doctrine\ORM\EntityManager;
use PHPUnit_Framework_Constraint_And;

class AssertSetters extends DescribingMatcher {

    protected $em;
    protected $data;

    public function __construct(EntityManager $em, $data = []) {
        parent::__construct();
        $this->em = $em;
        $this->data = new GetSetData($data);
    }

    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function toString() {
        return $this->matchesDescribing(null)[1];
    }

    /**
     * @param $other
     * @return array
     */
    protected function matchesDescribing($object) {
        $constraints = [];
        $constraint = null;
        $meta = null;
        if ($object) {
            $meta = $this->em->getClassMetadata(get_class($object));
            $missingFields = [];
            foreach ($meta->getFieldNames() as $field) {
                if (!$meta->isIdentifier($field)) {
                    if (!$this->data->hasField($field)) {
                        $missingFields[] = $field;
                    }
                }
            }
            if (!empty($missingFields)) {
                $constraint = new AlwaysFailConstraint("all fields are covered, missing " . implode(", ", $missingFields));
            }
        }
        foreach ($this->data->getFields() as $field) {
            if ($meta && !$meta->hasField($field)) {
                $constraint = new AlwaysFailConstraint("Unknown field $field");
            }
            $setter = self::findSetter($object, $field);
            if (!$setter) {
//                self::fail($object, "Missing setter for $field");
            }
            $constraints[] = UnitTestHelpers::methodAccepts($setter, [$this->data->getValue($field)]);
        }
        $matches = true;
        $failures = [];
        foreach ($constraints as $c) {
            if (!$c->matches($object)) {
                $matches = false;
                $failures[] = $c->failureDescription($object);
            }
        }
        return [$matches, implode(" and ", $failures)];
    }

    private static function findSetter($object, $field) {
        if (method_exists($object, "set" . ucfirst($field))) {
            return "set" . ucfirst($field);
        }
        return null;
    }
}
