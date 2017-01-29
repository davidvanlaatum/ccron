<?php

namespace Tests\CCronBundle\Assert;


use Doctrine\ORM\EntityManager;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\Factory;

class SameEntity extends DescribingMatcher {
    protected $object;
    protected $em;

    /**
     * @param $object
     * @param EntityManager $em
     */
    public function __construct($object, EntityManager $em) {
        parent::__construct();
        $this->object = $object;
        $this->em = $em;
    }

    /**
     * @param $other
     * @return array
     */
    protected function matchesDescribing($other) {
        if ($other === $this->object) {
            return [true, null];
        } else {
            $meta = $this->em->getClassMetadata(get_class($this->object));
            $reflectionClass = $meta->getReflectionClass();
            if (!$other || !$reflectionClass->isInstance($other)) {
                return [false, $this->exporter->shortenedExport($other) . ' is an instance of ' . $reflectionClass->getName()];
            } else {
                $id1 = $meta->getIdentifierValues($this->object);
                $id2 = $meta->getIdentifierValues($other);
                try {
                    Factory::getInstance()->getComparatorFor($id1, $id2)->assertEquals($id1, $id2);
                    return [true, null];
                } catch (ComparisonFailure $e) {
                    return [false, $e->getDiff()];
                }
            }
        }
    }
}
