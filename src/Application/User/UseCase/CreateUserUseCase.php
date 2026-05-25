<?php

namespace App\Application\User\UseCase;

use App\Application\User\DTO\Request\CreateUserRequest;
use App\Application\User\DTO\Response\UserInfoResponse;
use App\Domain\User\Entity\User;
use App\Domain\User\Event\UserCreatedEvent;
use App\Domain\User\Repository\UserRepositoryInterface;
use Exception;
use Random\RandomException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class CreateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @throws RandomException
     * @throws Exception
     */
    public function __invoke(CreateUserRequest $request): UserInfoResponse
    {
        $user = User::create(
            $request->email,
            $request->firstName,
            $request->lastName,
        );

        $user->generateMagicLinkToken();
        $this->userRepository->save($user);

        $this->eventDispatcher->dispatch(new UserCreatedEvent($user));

        return UserInfoResponse::fromEntity($user);
    }
}
