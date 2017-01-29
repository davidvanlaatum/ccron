<?php
namespace Tests\CCronBundle;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait DBAwareTestTrait {

    private static $schemaUpdated = false;

    /** @var EntityManager */
    protected $em;
    /**
     * @var ORMExecutor
     */
    private $fixtureExecutor;
    /**
     * @var ContainerAwareLoader
     */
    private $fixtureLoader;

    /**
     * @before
     */
    public function createDB() {
        $this->em = $this->getContainer()->get("doctrine.orm.default_entity_manager");
        if (!static::$schemaUpdated) {
            $schemaTool = new SchemaTool($this->em);
            $metadata = $this->em->getMetadataFactory()->getAllMetadata();
            $schemaTool->updateSchema($metadata);
            static::$schemaUpdated = true;
        }
        $this->executeFixtures();
    }

    /**
     * Executes all the fixtures that have been loaded so far.
     */
    protected function executeFixtures() {
        $this->getFixtureExecutor()->execute($this->getFixtureLoader()->getFixtures());
    }

    /**
     * @return ORMExecutor
     */
    private function getFixtureExecutor() {
        if (!$this->fixtureExecutor) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $this->fixtureExecutor = new ORMExecutor($this->em, new ORMPurger($this->em));
        }
        return $this->fixtureExecutor;
    }

    /**
     * @return ContainerAwareLoader
     */
    private function getFixtureLoader() {
        if (!$this->fixtureLoader) {
            $this->fixtureLoader = new ContainerAwareLoader($this->getContainer());
        }
        return $this->fixtureLoader;
    }

    /** @return ContainerInterface */
    abstract function getContainer();

    /**
     * @after
     */
    public function dropDB() {
        if ($this->em) {
            $purger = new ORMPurger($this->em);
            $purger->setPurgeMode(ORMPurger::PURGE_MODE_DELETE);
            $purger->purge();
        }
    }

    /**
     * Adds a new fixture to be loaded.
     *
     * @param FixtureInterface $fixture
     */
    protected function addFixture(FixtureInterface $fixture) {
        $this->getFixtureLoader()->addFixture($fixture);
    }
}
