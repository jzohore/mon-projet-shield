<?php

namespace App\Application\User\UseCase;

use App\Application\User\DTO\Request\UpdateNameRequest;
use App\Domain\User\Repository\UserRepositoryInterface;
use Webmozart\Assert\Assert;

final readonly class UpdateNameUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(UpdateNameRequest $request): void
    {
        Assert::notNull($request->firstName);
        Assert::notNull($request->lastName);
        Assert::notNull($request->userSlugId);

        $user = $this->userRepository->findBySlug($request->userSlugId);
        Assert::notNull($user);
        $user->updateName($request->firstName, $request->lastName);
        $this->userRepository->save($user);
    }
}
