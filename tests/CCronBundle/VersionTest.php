<?php

class VersionTest extends PHPUnit_Framework_TestCase {

    public function testSymfonyVersion() {
        $time = DateTime::createFromFormat("!m/Y", AppKernel::END_OF_MAINTENANCE);
        self::assertInstanceOf(DateTime::class, $time);
        self::assertGreaterThan(new DateTime(), $time, "Reached end of life");
    }
}
