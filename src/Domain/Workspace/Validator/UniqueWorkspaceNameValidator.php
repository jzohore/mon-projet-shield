<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Validator;

use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UniqueWorkspaceNameValidator extends ConstraintValidator
{
    public function __construct(
        private readonly WorkspaceRepositoryInterface $workspaceRepository
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueWorkspaceName) {
            throw new UnexpectedTypeException($constraint, UniqueWorkspaceName::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        // On cherche s'il existe déjà un cabinet avec ce nom exact
        $existingWorkspace = $this->workspaceRepository->findOneByName($value);
        if ($existingWorkspace !== null) {
            if ($constraint->ignoreSlugId !== null && $existingWorkspace->slugId === $constraint->ignoreSlugId) {
                return;
            }

            // Sinon, c'est que le nom est vraiment pris par quelqu'un d'autre
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', (string) $value)
                ->addViolation();
        }

    }
}
