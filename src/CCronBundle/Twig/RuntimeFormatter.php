<?php
namespace CCronBundle\Twig;

use CCronBundle\Clock;
use Twig_Extension;

class RuntimeFormatter extends Twig_Extension {
    private static $units = [
        'd' => 86400,
        'h' => 3600,
        'm' => 60,
        's' => 1,
        'ms' => 0.001
    ];

    /** @var Clock */
    protected $clock;

    public function __construct(Clock $clock) {
        $this->clock = $clock;
    }

    public function getFilters() {
        return [
            new \Twig_SimpleFilter('runtime', [$this, 'runtimeFilter']),
        ];
    }

    public function runtimeFilter($interval, \DateTime $start = null) {
        $rt = "";

        if ($interval === null && $start !== null) {
            $rt = ">";
            $interval = $this->clock->getTimeOfDay() - $start->getTimestamp();
        }

        if ($interval <= 0) {
            $rt = $interval;
        } else {
            foreach (self::$units as $extention => $unit) {
                if ($interval <= 0) {
                    break;
                }
                if ($interval >= $unit) {
                    $value = round($interval / $unit, 0, PHP_ROUND_HALF_DOWN);
                    $rt .= sprintf("%d%s", $value, $extention);
                    $interval -= $value * $unit;
                }
            }
        }

        return $rt;
    }
}
