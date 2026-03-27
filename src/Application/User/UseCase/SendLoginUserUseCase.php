<?php

namespace App\Application\User\UseCase;

use App\Application\User\DTO\Request\LoginUserRequest;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\User\Message\SendMagicLinkMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

final readonly class SendLoginUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private MessageBusInterface $messageBus,
        private UrlGeneratorInterface $router,
    ) {}

    public function __invoke(LoginUserRequest $request): void
    {
        Assert::notNull($request->email);

        $user = $this->userRepository->findByEmail($request->email);
        if (!$user) {
            return;
        }
        Assert::isInstanceOf($user, User::class);
        $user->clearMagicLinkToken();
        $user->generateMagicLinkToken();
        $this->userRepository->save($user);

        $magicLinkUrl = $this->router->generate('app_verify_magic_link', [
            'token' => $user->magicLinkToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $message = new SendMagicLinkMessage(
            $user->email,
            $user->magicLinkToken,
            $magicLinkUrl,
        );

        $this->messageBus->dispatch($message);
    }
}
