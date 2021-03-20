<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueValue extends Constraint
{
    public $message = 'This value is already used';
    public $field;
    public $class;

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
