<?php
namespace Tests\CCronBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

trait ContainerAwareTestCase {
    private $container;

    /** @return ContainerInterface */
    function getContainer() {
        if ($this->container == null) {
            if (!static::$kernel && method_exists($this, 'bootKernel')) {
                static::bootKernel();
            }
            if (static::$kernel instanceof KernelInterface) {
                $this->container = static::$kernel->getContainer();
                if (!$this->container) {
                    static::bootKernel();
                    $this->container = static::$kernel->getContainer();
                }
            } else {
                throw new \InvalidArgumentException("Can't get app kernel");
            }
            if (!($this->container instanceof ContainerInterface)) {
                throw new \InvalidArgumentException("Failed to get container");
            }
        }
        return $this->container;
    }

    /**
     * @after
     */
    public function clearContainer() {
        $this->container = null;
    }
}
