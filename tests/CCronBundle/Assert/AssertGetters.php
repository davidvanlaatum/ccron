<?php
namespace Tests\CCronBundle\Assert;

use Doctrine\ORM\EntityManager;

class AssertGetters extends DescribingMatcher {

    protected $em;
    /** @var GetSetData */
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
        return $this->matchesDescribing(null);
    }

    /**
     * @param $other
     * @return array
     */
    protected function matchesDescribing($object) {
        $constraints = [];
        $meta = null;
        if ($object) {
            $meta = $this->em->getClassMetadata(get_class($object));
            $missingFields = [];
            $constraint = null;
            foreach ($meta->getFieldNames() as $field) {
                if (!$meta->isIdentifier($field)) {
                    if (!$this->data->hasField($field)) {
                        $missingFields[] = $field;
                    }
                }
            }
            if ($this->em->contains($object)) {
                foreach ($meta->getIdentifierFieldNames() as $index => $field) {
                    $getter = self::findGetter($object, $field);
                    if (!$getter) {
                        self::fail($object, "Missing getter for $field");
                    }
                    $constraints[] = UnitTestHelpers::methodReturns($getter, $meta->getIdentifierValues($object)[$field]);
                }
            }
            if (!empty($missingFields)) {
                self::fail($object, "The following fields are missing " . implode(", ", $missingFields));
            }
        }
        foreach ($this->data->getFields() as $field) {
            if ($meta && !$meta->hasField($field)) {
                self::fail($object, "Unknown field $field");
            }
            $getter = self::findGetter($object, $field);
            if (!$getter) {
                self::fail($object, "Missing getter for $field");
            }
            $constraints[] = UnitTestHelpers::methodReturns($getter, $this->data->getExpectedValue($field));
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

    private static function findGetter($object, $field) {
        $options = ["get" . ucfirst($field), "is" . ucfirst($field)];
        foreach ($options as $option) {
            if (method_exists($object, $option)) {
                return $option;
            }
        }
        return null;
    }
}
