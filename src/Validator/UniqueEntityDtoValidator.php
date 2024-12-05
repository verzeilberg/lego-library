<?php
namespace App\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the DTO for entity-uniqueness
 */
class UniqueEntityDtoValidator extends ConstraintValidator
{
    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    /**
     * @param mixed $value
     * @param Constraint $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueEntityDto) {
            throw new UnexpectedTypeException($constraint, UniqueEntityDto::class);
        }

        $repository = $this->entityManager->getRepository($constraint->entityClass);
        if (null === $repository) {
            throw new InvalidArgumentException(sprintf('The repository for %s was not found.', $constraint->entityClass));
        }

        $entity = $repository->findOneBy([$constraint->field => $value->{$constraint->field}]);
        if (null !== $entity) {
            $this->context->buildViolation($constraint->existsMessage)
                ->addViolation();
        }
    }
}
