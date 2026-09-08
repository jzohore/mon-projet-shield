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
 * @extends Voter<string, WorkspaceInvitation|Workspace>
 */
final class WorkspaceInvitationVoter extends Voter
{
    public const string CREATE = 'INVITATION_CREATE';
    public const string RESEND = 'INVITATION_RESEND';
    public const string REVOKE = 'INVITATION_REVOKE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return match ($attribute) {
            self::CREATE => $subject instanceof Workspace,
            self::RESEND, self::REVOKE => $subject instanceof WorkspaceInvitation,
            default => false,
        };
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            $vote?->addReason('The user is not logged in.');

            return false;
        }

        // Le Super Admin KYSURE a tous les droits.
        if ($this->accessDecisionManager->decide($token, ['ROLE_SUPER_ADMIN'])) {
            return true;
        }

        $workspace = $subject instanceof WorkspaceInvitation ? $subject->workspace : $subject;

        // Seul un administrateur de l'espace de travail concerné peut gérer les invitations.
        return $this->workspaceMemberRepository->isUserAdminOfWorkspace(user: $user, workspace: $workspace);
    }
}
