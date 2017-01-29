<?php
namespace CCronBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Cron extends Constraint {
    public $message = 'The string "%string%" is not a valid cron schedule';
}
