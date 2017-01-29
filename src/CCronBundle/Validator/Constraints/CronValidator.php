<?php
namespace CCronBundle\Validator\Constraints;

use Cron\CronExpression;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CronValidator extends ConstraintValidator {
    public function validate($value, Constraint $constraint) {
        try {
            CronExpression::factory($value);
        } catch (\InvalidArgumentException $exception) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%string%', $value)
                ->addViolation();
        }
    }
}
