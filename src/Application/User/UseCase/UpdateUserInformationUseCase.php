<?php

declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Application\User\DTO\Request\UserProfilRequest;
use App\Domain\User\Entity\User;
use App\Domain\User\Event\UserUpdateProfilEvent;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

readonly class UpdateUserInformationUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(UserProfilRequest $request, User $user, Workspace $workspace): void
    {
        Assert::notNull($request->firstName);
        Assert::notNull($request->lastName);

        $user->updateProfilInformations(
            firstName: $request->firstName,
            lastName: $request->lastName,
            jobRole: $request->jobTitle,
            phone: $request->phoneNumber,
        );

        $this->userRepository->save($user);

        $this->eventDispatcher->dispatch(new UserUpdateProfilEvent(
            user: $user,
            workspace: $workspace,
        ));
    }
}
