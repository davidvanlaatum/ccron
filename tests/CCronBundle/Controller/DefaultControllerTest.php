<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\CCronBundle\ContainerAwareTestCase;
use Tests\CCronBundle\DBAwareTestTrait;

class DefaultControllerTest extends WebTestCase {
    use ContainerAwareTestCase;
    use DBAwareTestTrait;

    public function testIndex() {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('Schedule', $crawler->filter('thead')->text());
    }
}
