<?php

namespace App\Application\User\UseCase;

use App\Application\User\DTO\Request\LoginUserRequest;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\User\Message\SendMagicLinkMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

final readonly class SendLoginUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private MessageBusInterface $messageBus,
    ) {}

    public function __invoke(LoginUserRequest $request): void
    {
        Assert::notNull($request->email);

        $user = $this->userRepository->findByEmail($request->email);
        Assert::isInstanceOf($user, User::class);
        $user->clearMagicLinkToken();
        $user->generateMagicLinkToken();
        $this->userRepository->save($user);
        $message = new SendMagicLinkMessage(
            $user->email,
            $user->magicLinkToken,
        );

        $this->messageBus->dispatch($message);
    }
}
