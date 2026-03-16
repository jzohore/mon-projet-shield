<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Validator;

use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UniqueWorkspaceInvitationEmailValidator extends ConstraintValidator
{
    public function __construct(
        private readonly WorkspaceInvitationRepositoryInterface $workspaceRepository
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueWorkspaceInvitationEmail) {
            throw new UnexpectedTypeException($constraint, UniqueWorkspaceInvitationEmail::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        // On cherche s'il existe déjà un cabinet avec ce nom exact
        $existingWorkspace = $this->workspaceRepository->findByEmail($value);

        // S'il existe...
        if ($existingWorkspace !== null) {

            // 💡 LA NOUVELLE LOGIQUE D'EXCLUSION
            // Si on a fourni un ID à ignorer, et que cet ID correspond au cabinet trouvé,
            // c'est qu'on est juste en train de sauvegarder son propre nom. Tout va bien !
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
