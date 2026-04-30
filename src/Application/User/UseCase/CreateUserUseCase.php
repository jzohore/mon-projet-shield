<?php

namespace App\Application\User\UseCase;

use App\Application\User\DTO\Request\CreateUserRequest;
use App\Application\User\DTO\Response\UserInfoResponse;
use App\Domain\User\Entity\User;
use App\Domain\User\Event\UserCreatedEvent;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class CreateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(CreateUserRequest $request): UserInfoResponse
    {
        $user = User::create(
            $request->email,
            $request->firstName,
            $request->lastName,
        );
        if ($request->isAdmin) {
            $user->promoteToAdmin();
        }
        $user->generateMagicLinkToken();
        $this->userRepository->save($user);

        $this->eventDispatcher->dispatch(new UserCreatedEvent($user));

        return UserInfoResponse::fromEntity($user);
    }
}
