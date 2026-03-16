<?php

namespace App\Application\User\UseCase;

use App\Application\User\DTO\Request\CreateUserRequest;
use App\Domain\User\Entity\User;
use App\Domain\User\Event\UserCreatedEvent;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

final readonly class CreateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(CreateUserRequest $request): void
    {
        Assert::notnull($request->email);
        Assert::notNull($request->firstName);
        Assert::notNull($request->lastName);

        if ($this->userRepository->findByEmail($request->email)) {
            // L'utilisateur existe déjà. On s'arrête silencieusement.
            // Bonus plus tard : lancer un UserAlreadyExistsEvent pour lui envoyer un email "Connectez-vous plutôt ici"
            return;
        }
        $user = new User(
            $request->email,
            $request->firstName,
            $request->lastName,
            $request->isVerified,
        );
        if ($request->isAdmin) {
            $user->promoteToAdmin();
        }
        $user->generateMagicLinkToken();
        $this->userRepository->save($user);

        $this->eventDispatcher->dispatch(new UserCreatedEvent($user));
    }
}
