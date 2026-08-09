<?php

declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Application\User\DTO\Request\UserProfilRequest;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use Webmozart\Assert\Assert;

readonly class UpdateUserInformationUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private CurrentUserProvider $currentUserProvider,
    ) {
    }

    public function __invoke(UserProfilRequest $request): void
    {
        Assert::notNull($request->firstName);
        Assert::notNull($request->lastName);

        $user = $this->currentUserProvider->getUser();
        $user->updateProfilInformations(
            firstName: $request->firstName,
            lastName: $request->lastName,
            jobRole: $request->jobTitle,
            phone: $request->phoneNumber,
        );
        $this->userRepository->save($user);
    }
}
