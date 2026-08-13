<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Webmozart\Assert\Assert;

final readonly class WorkspaceStatusChecker implements UserCheckerInterface
{
    public function __construct(
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
    ) {
    }

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // 1. Vérification du statut de l'utilisateur lui-même
        if (!$user->isActif) {
            throw new CustomUserMessageAccountStatusException('Votre compte utilisateur a été désactivé.');
        }

        // 2. Vérification du statut du Cabinet (Workspace)
        Assert::notNull($user->id);
        $workspaceMember = $this->workspaceMemberRepository->findOneByUser($user->id);
        Assert::notNull($workspaceMember);
        $workspace = $workspaceMember->workspace;
        if (!$workspace->isActive) {
            throw new CustomUserMessageAccountStatusException('L\'accès à ce cabinet a été suspendu pour raisons administratives. Veuillez contacter le support KYSURE.');
        }
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        // Rien à vérifier après l'authentification dans notre cas.
    }
}
