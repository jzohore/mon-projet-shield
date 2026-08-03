<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Voter;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, WorkspaceInvitation>
 */
final class WorkspaceInvitationVoter extends Voter
{
    public const string RESEND = 'INVITATION_RESEND';
    public const string REVOKE = 'INVITATION_REVOKE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Ce voter ne s'active QUE pour ces deux actions ET si le sujet est bien une Invitation
        return in_array($attribute, [self::RESEND, self::REVOKE], true)
            && $subject instanceof Workspace;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        // 1. L'utilisateur doit être connecté
        if (!$user instanceof User) {
            $vote?->addReason('The user is not logged in.');

            return false;
        }

        // 2. Le Super Admin (Equipe Shield) a tous les droits, on gagne du temps
        if ($this->accessDecisionManager->decide($token, ['ROLE_SUPER_ADMIN'])) {
            return true;
        }

        $workspace = $subject->workspace;

        // 3. Vérification de la permission contextuelle (Multi-tenant B2B)
        // On interroge la base pour savoir si l'utilisateur courant a le droit d'administrer ce Workspace
        return match ($attribute) {
            self::RESEND, self::REVOKE => $this->canManageInvitation($user, $workspace),
            default => false,
        };
    }

    private function canManageInvitation(User $user, Workspace $workspace): bool
    {
        // 💡 Règle métier : Seul un Administrateur du Workspace peut révoquer ou renvoyer une invitation.
        // À toi d'adapter cette méthode selon ton interface de repository.
        return $this->workspaceMemberRepository->isUserAdminOfWorkspace(user: $user, workspace: $workspace);
    }
}
