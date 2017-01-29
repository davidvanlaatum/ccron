<?php
namespace Tests\CCronBundle\Assert;

use Doctrine\ORM\EntityManager;

trait UnitTestHelpers {
    public static function assertGetters($object, EntityManager $em, $data) {
        self::assertThat($object, self::getters($em, $data));
    }

    public static function getters(EntityManager $em, $data = []) {
        return new AssertGetters($em, $data);
    }

    public static function assertSetters($object, EntityManager $em, $data) {
        self::assertThat($object, self::setters($em, $data));
    }

    public static function setters(EntityManager $em, $data = []) {
        return new AssertSetters($em, $data);
    }

    /*
        private static function assertSettersAndGetters(EntityManager $em, $object, $data) {
            $meta = $em->getClassMetadata(get_class($object));
            $missingFields = [];
            $constraints = [];
            foreach ($meta->getFieldNames() as $field) {
                if (!$meta->isIdentifier($field)) {
                    if (!array_key_exists($field, $data)) {
                        $missingFields[] = $field;
                    }
                }
            }
            if ($em->contains($object)) {
                foreach ($meta->getIdentifierFieldNames() as $index => $field) {
                    $getter = self::findGetter($object, $field);
                    if (!$getter) {
                        self::fail("Missing getter for $field");
                    }
                    $constraints[] = self::methodReturns($getter, $meta->getIdentifierValues($object)[$field]);
                }
            }
            if (!empty($missingFields)) {
                self::fail("The following fields are missing " . implode(", ", $missingFields));
            }
            foreach ($data as $field => $value) {
                if (!$meta->hasField($field)) {
                    self::fail("Unknown field $field");
                }
                $getter = self::findGetter($object, $field);
                if (!$getter) {
                    self::fail("Missing getter for $field");
                }
                $setter = self::findSetter($object, $field);
                if (!$setter) {
                    self::fail("Missing setter for $field");
                }
                $object->$setter($value);
                $constraints[] = self::methodReturns($getter, $value);
            }
            $constraint = new PHPUnit_Framework_Constraint_And();
            $constraint->setConstraints($constraints);
            self::assertThat($object, $constraint);
            return $data;
        }

        private static function findGetter($object, $field) {
            $options = ["get" . ucfirst($field), "is" . ucfirst($field)];
            foreach ($options as $option) {
                if (method_exists($object, $option)) {
                    return $option;
                }
            }
            return null;
        }*/

    public static function methodReturns($method, $constraint) {
        return new MethodReturns($method, $constraint);
    }

    public static function methodAccepts($method, $args) {
        return new MethodAccepts($method, $args);
    }

    private static function findSetter($object, $field) {
        if (method_exists($object, "set" . ucfirst($field))) {
            return "set" . ucfirst($field);
        }
        return null;
    }
}
