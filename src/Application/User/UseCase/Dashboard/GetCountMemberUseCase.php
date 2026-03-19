<?php

namespace App\Application\User\UseCase\Dashboard;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;

final readonly class GetCountMemberUseCase
{
    public function __construct(
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
    ) {}

    public function __invoke(User $user): int
    {
        return count($this->workspaceMemberRepository->findByUser($user));
    }
}
