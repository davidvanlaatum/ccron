<?php

namespace Tests\CCronBundle;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Tests\CCronBundle\Constraints\Web\IsRedirect;

trait WebTestTrait {

    /**
     * @param string|\Net_URL2 $url
     * @return string
     */
    public static function fixURL($url) {
        if (!($url instanceof \Net_URL2)) {
            $url = new \Net_URL2($url);
        }
        if (!$url->getHost()) {
            $url->setHost('localhost');
        }
        if (!$url->getScheme()) {
            $url->setScheme('http');
        }
        return $url->getURL();
    }

    /** @return Router */
    protected function router() {
        return $this->getContainer()->get("router");
    }

    protected function logIn(Client $client) {
        $userManager = $client->getContainer()->get('fos_user.user_manager');
        $user = $userManager->findUserByUsername('unittest');
        if (!$user) {
            $user = $userManager->createUser();
        }
        $user->setUsername('unittest');
        $user->setEnabled(true);
        $user->setSuperAdmin(true);
        $user->setEmail('test@example.com');
        $user->setPlainPassword('test');
        $userManager->updateUser($user);
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('_submit')->form();
        $form->get('_username')->setValue($user->getUsername());
        $form->get('_password')->setValue('test');
        $client->submit($form);
        self::assertThat($client->getResponse(), self::isRedirectTo('/'));
    }

    protected static function isRedirectTo($location) {
        return new IsRedirect($location);
    }
}
