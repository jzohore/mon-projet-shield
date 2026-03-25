<?php

namespace App\Application\User\UseCase;

use App\Application\User\DTO\Response\UserInfoResponse;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use Webmozart\Assert\Assert;

final readonly class GetCurrentUserInfoUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(string $userSlugId): UserInfoResponse
    {
        $user = $this->userRepository->findBySlug($userSlugId);

        Assert::isInstanceOf($user, User::class, sprintf('Aucun utilisateur trouvé pour le slug "%s"', $userSlugId));

        return UserInfoResponse::fromEntity($user);
    }
}
