<?php

namespace App\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueValueValidator extends ConstraintValidator
{
    private $propertyAccessor;
    private $registry;

    public function __construct(
        ManagerRegistry $registry,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->registry = $registry;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function validate($submittedEntity, Constraint $constraint): void
    {
        $em = $this->registry->getManagerForClass($constraint->class);
        $repository = $em->getRepository($constraint->class);

        $existingEntity = $repository->findOneBy([
            $constraint->field => $this->propertyAccessor->getValue($submittedEntity, "$constraint->field")
        ]);

        $submittedEntityId = $this->propertyAccessor->getValue($submittedEntity, 'id');
        if (!$existingEntity || $existingEntity->getId() === $submittedEntityId) {
            return;
        }

        $metadata = $em->getClassMetadata($constraint->class);
        $fieldValue = $metadata->reflFields[$constraint->field]->getValue($existingEntity);

        $this->context->buildViolation($constraint->message)
            ->atPath($constraint->field)
            ->setParameter('{{ value }}', $fieldValue)
            ->addViolation()
        ;
    }
}
