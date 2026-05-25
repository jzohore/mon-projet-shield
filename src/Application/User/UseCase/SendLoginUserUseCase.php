<?php

declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Application\User\DTO\Request\LoginUserRequest;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\User\Message\SendMagicLinkMessage;
use Random\RandomException;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

readonly class SendLoginUserUseCase
{
    /**
     * @param UserRepositoryInterface $userRepository
     * @param MessageBusInterface $messageBus
     * @param UrlGeneratorInterface $router
     */
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private MessageBusInterface $messageBus,
        private UrlGeneratorInterface $router,
    ) {}

    /**
     * @param LoginUserRequest $request
     * @return void
     * @throws ExceptionInterface|RandomException
     */
    public function __invoke(LoginUserRequest $request): void
    {
        $user = $this->userRepository->getByEmail($request->email);

        $user->clearMagicLinkToken();
        $user->generateMagicLinkToken();
        $this->userRepository->save($user);

        Assert::notNull($user->email);
        Assert::notNull($user->magicLinkToken);

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
