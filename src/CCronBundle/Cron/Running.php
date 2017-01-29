<?php
namespace CCronBundle\Cron;

use Symfony\Bridge\Monolog\Logger;

class Running {
    protected $running = true;
    protected $memory_limit;
    /** @var Logger */
    protected $logger;

    /**
     * Running constructor.
     */
    public function __construct(Logger $logger) {
        $this->memory_limit = $this->returnBytes(ini_get('memory_limit'));
        $this->logger = $logger;
    }

    function returnBytes($val) {
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
            $this->running = false;
            $this->logger->warn("Hit memory limit shutting down");
        }
        return $this->running;
    }

    public function shutdown() {
        $this->running = false;
    }
}
