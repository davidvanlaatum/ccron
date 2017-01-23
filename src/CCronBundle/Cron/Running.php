<?php
namespace CCronBundle\Cron;
class Running {
    protected $running = true;

    public function isRunning() {
        return $this->running;
    }

    public function shutdown() {
        $this->running = false;
    }
}
