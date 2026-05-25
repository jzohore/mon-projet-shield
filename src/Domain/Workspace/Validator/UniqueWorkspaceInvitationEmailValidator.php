<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Validator;

use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UniqueWorkspaceInvitationEmailValidator extends ConstraintValidator
{
    public function __construct(
        private readonly WorkspaceInvitationRepositoryInterface $invitationRepository,
        private readonly WorkspaceMemberRepositoryInterface $memberRepository,
        private readonly CurrentWorkspaceProvider $currentWorkspaceProvider, // 🪄 L'ingrédient secret !
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueWorkspaceInvitationEmail) {
            throw new UnexpectedTypeException($constraint, UniqueWorkspaceInvitationEmail::class);
        }

        // Les valeurs vides sont gérées par #[Assert\NotBlank]
        if (null === $value || '' === $value) {
            return;
        }

        $email = (string) $value;
        $workspace = $this->currentWorkspaceProvider->getWorkspace();

        // 🛡️ 1. Vérification : Est-il déjà membre actif ?
        if ($this->memberRepository->isAlreadyMember($workspace, $email)) {
            // On peut même surcharger le message par défaut de la contrainte ici
            $this->context->buildViolation('Cet utilisateur est déjà membre de cet espace de travail.')
                ->setParameter('{{ value }}', $email)
                ->addViolation();

            return; // On arrête la validation ici
        }

        // 🛡️ 2. Vérification : A-t-il déjà une invitation en attente ?
        if ($this->invitationRepository->hasPendingInvitation($workspace, $email)) {
            // Là on utilise le message défini dans ton attribut #[UniqueWorkspaceInvitationEmail]
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $email)
                ->addViolation();
        }
    }
}
