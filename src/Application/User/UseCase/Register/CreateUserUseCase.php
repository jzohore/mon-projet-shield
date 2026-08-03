<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Register;

use App\Application\User\DTO\Request\CreateUserRequest;
use App\Domain\User\Entity\User;
use App\Domain\User\Event\UserRegisteredEvent;
use App\Domain\User\Exception\UserAlreadyExistsException;
use App\Domain\User\Repository\UserRepositoryInterface;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class CreateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws RandomException
     * @throws \Exception
     */
    public function __invoke(CreateUserRequest $request): void
    {
        if ($this->userRepository->existsByEmail($request->email)) {
            throw UserAlreadyExistsException::withEmail($request->email);
        }

        $user = User::create(
            $request->email,
            $request->firstName,
            $request->lastName,
        );

        $user->generateMagicLinkToken();
        $this->userRepository->save($user);

        $this->logger->info('Un nouvel utilisateur a été créé avec succès.', [
            'email' => $user->getNormalizedEmail(),
            'user_id' => $user->slugId,
        ]);

        $this->eventDispatcher->dispatch(new UserRegisteredEvent(
            userId: $user->slugId,
            email: $user->getNormalizedEmail(),
            fullName: $user->getFullName(),
            magicLinkToken: $user->magicLinkToken,
        ));
    }
}
