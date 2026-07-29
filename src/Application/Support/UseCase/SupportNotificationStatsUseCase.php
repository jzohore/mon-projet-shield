<?php

declare(strict_types=1);

namespace App\Application\Support\UseCase;

use App\Application\Support\DTO\Response\SupportNotificationStats;
use App\Domain\Support\Repository\SupportThreadRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Webmozart\Assert\Assert;

readonly class SupportNotificationStatsUseCase
{
    public function __construct(
        private Security $security,
        private CurrentWorkspaceProvider $workspaceProvider,
        private SupportThreadRepositoryInterface $threadRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(): SupportNotificationStats
    {
        $email = $this->security->getUser();
        Assert::notNull($email);
        $user = $this->userRepository->getByEmail($email->getUserIdentifier());
        // Si non connecté ou sans workspace, on renvoie les stats à 0

        $workspace = $this->workspaceProvider->getWorkspace();

        return $this->threadRepository->getNotificationStats($workspace, $user);
    }
}
