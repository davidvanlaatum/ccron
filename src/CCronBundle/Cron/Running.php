<?php
namespace CCronBundle\Cron;

use Psr\Log\LoggerInterface;

class Running {
    protected $running = true;
    protected $memory_limit;
    /** @var LoggerInterface */
    protected $logger;

    public function __construct(LoggerInterface $logger) {
        $this->memory_limit = $this->returnBytes(ini_get('memory_limit'));
        $this->logger = $logger;
    }

    public function returnBytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        switch ($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        return $val;
    }

    public function isRunning() {
        if ($this->running && memory_get_usage() > $this->memory_limit * 0.85) {
            $this->logger->warning("Hit memory limit shutting down");
            $this->shutdown();
        }
        return $this->running;
    }

    public function shutdown() {
        if (!$this->running) {
            $this->logger->warning("Calling shutdown", debug_backtrace(0, 1));
        }
        $this->running = false;
    }
}
