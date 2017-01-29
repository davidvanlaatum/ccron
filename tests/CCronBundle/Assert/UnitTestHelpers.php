<?php
namespace Tests\CCronBundle\Assert;

use Doctrine\ORM\EntityManager;

trait UnitTestHelpers {
    public static function assertGettersAndSetters($object, EntityManager $em, $data, $postClearDataModifier = null) {
        self::assertSetters($object, $em, $data);
        self::assertGetters($object, $em, $data);
        $em->persist($object);
        $em->flush();
        $em->clear();
        if (is_callable($postClearDataModifier)) {
            $data = $postClearDataModifier($data);
        } else if ($postClearDataModifier) {
            throw new \InvalidArgumentException("postClearDataModifier must be a closure");
        }
        $object = $em->find(get_class($object), $em->getClassMetadata(get_class($object))->getIdentifierValues($object));
        self::assertGetters($object, $em, $data);
    }

    public static function assertSetters($object, EntityManager $em, $data) {
        self::assertThat($object, self::setters($em, $data));
    }

    public static function setters(EntityManager $em, $data = []) {
        return new AssertSetters($em, $data);
    }

    public static function assertGetters($object, EntityManager $em, $data) {
        self::assertThat($object, self::getters($em, $data));
    }

    public static function getters(EntityManager $em, $data = []) {
        return new AssertGetters($em, $data);
    }

    public static function methodReturns($method, $constraint) {
        return new MethodReturns($method, $constraint);
    }

    public static function methodAccepts($method, $args) {
        return new MethodAccepts($method, $args);
    }

    public static function sameEntity($object, EntityManager $em) {
        return new SameEntity($object, $em);
    }
}
