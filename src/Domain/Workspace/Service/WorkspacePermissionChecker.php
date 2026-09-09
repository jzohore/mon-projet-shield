<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Service;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Enum\PermissionMode;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;

/**
 * Source unique de vérité pour « ce qu'un utilisateur peut faire dans ce cabinet ».
 * Un administrateur peut tout ce qui est ici ; un collaborateur membre ne peut
 * une action déléguée que si l'administrateur l'a ouverte. La gestion des droits
 * elle-même n'est jamais déléguée.
 */
readonly class WorkspacePermissionChecker
{
    public function __construct(
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
    ) {
    }

    public function isAdmin(User $user, Workspace $workspace): bool
    {
        return $this->workspaceMemberRepository->isUserAdminOfWorkspace($user, $workspace);
    }

    public function canInvite(User $user, Workspace $workspace): bool
    {
        return $this->allowed($user, $workspace, $workspace->collabCanInvite);
    }

    public function canEditCabinet(User $user, Workspace $workspace): bool
    {
        return $this->allowed($user, $workspace, $workspace->collabCanEditCabinet);
    }

    public function canManagePortfolio(User $user, Workspace $workspace): bool
    {
        return $this->allowed($user, $workspace, $workspace->collabCanManagePortfolio);
    }

    public function canArchiveFolder(User $user, Workspace $workspace): bool
    {
        return $this->allowed($user, $workspace, $workspace->collabCanArchiveFolder);
    }

    /**
     * Valider / rejeter / révoquer un acte de conformité (dossier KYC, rapport
     * d'entretien). Un administrateur peut toujours ; un collaborateur seulement
     * si le cabinet est en mode « délégué ». Les modes « soumission » et
     * « réservé » exigent un administrateur (le circuit de soumission viendra).
     */
    public function canValidateActs(User $user, Workspace $workspace): bool
    {
        return $this->isAdmin($user, $workspace)
            || PermissionMode::DELEGATED === $workspace->validationMode;
    }

    private function allowed(User $user, Workspace $workspace, bool $delegated): bool
    {
        if ($this->isAdmin($user, $workspace)) {
            return true;
        }

        return $delegated && $this->isMember($user, $workspace);
    }

    private function isMember(User $user, Workspace $workspace): bool
    {
        return $this->workspaceMemberRepository->findByWorkspaceAndUser($workspace, $user) instanceof WorkspaceMember;
    }
}
