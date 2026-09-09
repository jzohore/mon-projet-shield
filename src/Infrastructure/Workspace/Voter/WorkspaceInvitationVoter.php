<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Voter;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Service\WorkspacePermissionChecker;
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
    /** Modifier la fiche / les réglages du cabinet. */
    public const string WORKSPACE_EDIT = 'WORKSPACE_EDIT';
    /** Ajouter un client au portefeuille du cabinet. */
    public const string PORTFOLIO_MANAGE = 'WORKSPACE_PORTFOLIO_MANAGE';
    /** Archiver un dossier de conformité. */
    public const string FOLDER_ARCHIVE = 'WORKSPACE_FOLDER_ARCHIVE';
    /** Ouvrir / restreindre les droits délégués aux collaborateurs — administrateurs uniquement. */
    public const string PERMISSIONS_MANAGE = 'WORKSPACE_PERMISSIONS_MANAGE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly WorkspacePermissionChecker $permissionChecker,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return match ($attribute) {
            self::CREATE, self::WORKSPACE_EDIT, self::PORTFOLIO_MANAGE, self::FOLDER_ARCHIVE, self::PERMISSIONS_MANAGE => $subject instanceof Workspace,
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

        return match ($attribute) {
            self::CREATE, self::RESEND, self::REVOKE => $this->permissionChecker->canInvite($user, $workspace),
            self::WORKSPACE_EDIT => $this->permissionChecker->canEditCabinet($user, $workspace),
            self::PORTFOLIO_MANAGE => $this->permissionChecker->canManagePortfolio($user, $workspace),
            self::FOLDER_ARCHIVE => $this->permissionChecker->canArchiveFolder($user, $workspace),
            self::PERMISSIONS_MANAGE => $this->permissionChecker->isAdmin($user, $workspace),
            default => false,
        };
    }
}
