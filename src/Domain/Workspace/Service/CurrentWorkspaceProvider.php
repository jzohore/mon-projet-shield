<?php

namespace App\Domain\Workspace\Service;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Webmozart\Assert\Assert;

final class CurrentWorkspaceProvider
{
    // Au lieu d'un tableau, on stocke directement l'entité.
    // En PHP, un script = une requête d'un seul utilisateur, donc un seul Workspace suffit !
    private ?Workspace $cachedWorkspace = null;

    public function __construct(
        private readonly WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private readonly Security $security,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function getWorkspace(): Workspace
    {
        // 1. Si on a déjà chargé l'entité pendant cette requête, on la renvoie direct (Cache)
        $userEmail = $this->security->getUser()?->getUserIdentifier();
        Assert::notNull($userEmail);
        $user = $this->userRepository->getByEmail($userEmail);
        Assert::notNull($user->id);
        if ($this->cachedWorkspace !== null) {
            return $this->cachedWorkspace;
        }

        // 2. Sinon, on va la chercher en base de données
        $workspaceMember = $this->workspaceMemberRepository->findOneByUser($user->id);
        Assert::notNull($workspaceMember, "L'utilisateur n'est membre d'aucun espace de travail.");

        $workspace = $workspaceMember->workspace;
        Assert::notNull($workspace, "L'espace de travail est introuvable.");

        // 3. On sauvegarde l'entité dans notre cache
        $this->cachedWorkspace = $workspace;

        return $this->cachedWorkspace;
    }
}
