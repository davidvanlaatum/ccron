<?php
namespace Tests\CCronBundle\Assert;

use Doctrine\ORM\EntityManager;

class AssertSetters extends DescribingMatcher {

    protected $em;
    protected $data;

    public function __construct(EntityManager $em, $data = []) {
        parent::__construct();
        $this->em = $em;
        $this->data = new GetSetData($data);
    }

    /**
     * @param $object
     * @return array
     */
    protected function matchesDescribing($object) {
        $constraints = [];
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
                $constraints[] = new AlwaysFailConstraint("The following fields are missing " . implode(", ", $missingFields));
            }
        }
        foreach ($this->data->getFields() as $field) {
            if ($meta && !($meta->hasField($field) || $meta->hasAssociation($field))) {
                $constraints[] = new AlwaysFailConstraint("Unknown field $field");
            } else {
                $setter = self::findSetter($object, $field);
                if (!$setter) {
                    $constraints[] = new AlwaysFailConstraint("Missing setter for $field");
                } else {
                    $constraints[] = UnitTestHelpers::methodAccepts($setter, [$this->data->getValue($field)]);
                }
            }
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
