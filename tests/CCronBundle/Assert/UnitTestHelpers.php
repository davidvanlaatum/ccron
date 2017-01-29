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

    public static function methodReturns($method, $constraint) {
        return new MethodReturns($method, $constraint);
    }

    public static function methodAccepts($method, $args) {
        return new MethodAccepts($method, $args);
    }
}
