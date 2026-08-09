<?php

declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Application\User\DTO\Request\LoginUserRequest;
use App\Domain\User\Entity\Admin;
use App\Domain\User\Entity\Client;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\UserType;
use App\Domain\User\Exception\UserNotFoundException;
use App\Domain\User\Repository\AdminRepositoryInterface;
use App\Domain\User\Repository\ClientRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\User\Message\SendMagicLinkMessage;
use Random\RandomException;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

readonly class SendLoginUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private MessageBusInterface $messageBus,
        private ClientRepositoryInterface $clientRepository,
        private AdminRepositoryInterface $adminRepository,
    ) {
    }

    /**
     * @throws ExceptionInterface|RandomException
     */
    public function __invoke(LoginUserRequest $request): void
    {
        $email = strtolower(trim($request->email));

        $user = $this->userRepository->findByEmail($email);
        if ($user instanceof User) {
            $this->handleCgpLogin($user);

            return;
        }

        $client = $this->clientRepository->findByEmail($email);
        if ($client instanceof Client) {
            $this->handleClientLogin($client);

            return;
        }

        $admin = $this->adminRepository->findByEmail($email);
        if ($admin instanceof Admin) {
            $this->handleAdminLogin($admin);

            return;
        }

        throw UserNotFoundException::withEmail($email);
    }

    /**
     * @throws RandomException
     * @throws ExceptionInterface
     */
    private function handleCgpLogin(User $user): void
    {
        $user->clearMagicLinkToken();
        $user->generateMagicLinkToken();

        $this->userRepository->save($user);

        $token = $user->magicLinkToken;
        Assert::stringNotEmpty($token, 'Le token Magic Link CGP n\'a pas pu être généré.');

        $this->messageBus->dispatch(new SendMagicLinkMessage(
            email: $user->email,
            magicLinkToken: $token,
            recipientType: UserType::CGP
        ));
    }

    /**
     * @throws RandomException
     * @throws ExceptionInterface
     */
    private function handleClientLogin(Client $client): void
    {
        $client->clearMagicLinkToken();
        $client->generateMagicLinkToken();

        $this->clientRepository->save($client);

        $token = $client->magicLinkToken;
        Assert::stringNotEmpty($token, 'Le token Magic Link Client n\'a pas pu être généré.');

        $this->messageBus->dispatch(new SendMagicLinkMessage(
            email: $client->email,
            magicLinkToken: $token,
            recipientType: UserType::CLIENT
        ));
    }

    /**
     * @throws RandomException
     * @throws ExceptionInterface
     */
    private function handleAdminLogin(Admin $admin): void
    {
        $admin->clearMagicLinkToken();
        $admin->generateMagicLinkToken();

        $this->adminRepository->save($admin);

        $token = $admin->magicLinkToken;
        Assert::stringNotEmpty($token, 'Le token Magic Link Admin n\'a pas pu être généré.');

        $this->messageBus->dispatch(new SendMagicLinkMessage(
            email: $admin->email,
            magicLinkToken: $token,
            recipientType: UserType::ADMIN
        ));
    }
}
