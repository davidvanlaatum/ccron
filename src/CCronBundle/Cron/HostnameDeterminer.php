<?php
namespace CCronBundle\Cron;

class HostnameDeterminer {
    protected $name;

    public function get() {
        return $this->name ? $this->name : gethostname();
    }

    public function set($name) {
        $this->name = $name;
    }
}
