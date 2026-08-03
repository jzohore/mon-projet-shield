<?php

declare(strict_types=1);

namespace App\Tests\Application\User\Login;

use App\Application\User\DTO\Request\LoginUserRequest;
use App\Application\User\UseCase\SendLoginUserUseCase;
use App\Domain\User\Entity\Admin;
use App\Domain\User\Entity\Client;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\UserType;
use App\Domain\User\Exception\UserNotFoundException;
use App\Domain\User\Repository\AdminRepositoryInterface;
use App\Domain\User\Repository\ClientRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\User\Message\SendMagicLinkMessage;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class LoginUserUseCaseTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepositoryMock;
    private ClientRepositoryInterface&MockObject $clientRepositoryMock;
    private AdminRepositoryInterface&MockObject $adminRepositoryMock;
    private MessageBusInterface&MockObject $messageBusMock;

    private SendLoginUserUseCase $useCase;

    protected function setUp(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->clientRepositoryMock = $this->createMock(ClientRepositoryInterface::class);
        $this->messageBusMock = $this->createMock(MessageBusInterface::class);
        $this->adminRepositoryMock = $this->createMock(AdminRepositoryInterface::class);

        $this->useCase = new SendLoginUserUseCase(
            userRepository: $this->userRepositoryMock,
            messageBus: $this->messageBusMock,
            clientRepository: $this->clientRepositoryMock,
            adminRepository: $this->adminRepositoryMock,
        );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testItHandlesCgpLoginSuccessfully(): void
    {
        // --- ARRANGE ---
        $email = 'cgp.pro@kysure.fr';
        $request = new LoginUserRequest(email: '  CGP.PRO@KYSURE.FR ');

        // CORRECTION : On instancie une VRAIE entité User au lieu d'un Mock !
        // Cela respecte la portée private(set) de PHP 8.4.
        $user = User::create(
            email: $email,
            firstName: 'Jean',
            lastName: 'Dupont'
        );

        // 1. Le repository CGP retourne notre vraie entité
        $this->userRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->clientRepositoryMock
            ->expects($this->never())
            ->method('findByEmail');

        // 2. Persistance de l'entité modifiée
        $this->userRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $savedUser) use ($email): bool {
                // On vérifie que le token a bien été généré sur l'entité
                $this->assertSame($email, $savedUser->email);
                $this->assertNotNull($savedUser->magicLinkToken);

                return true;
            }));

        // 3. Assertion sur le message Messenger dispatché
        $this->messageBusMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (SendMagicLinkMessage $message) use ($email, $user): bool {
                $this->assertSame($email, $message->email);
                $this->assertSame($user->magicLinkToken, $message->magicLinkToken);
                $this->assertSame(UserType::CGP, $message->recipientType);

                return true;
            }))
            ->willReturnCallback(static fn (SendMagicLinkMessage $msg): Envelope => new Envelope($msg));

        // --- ACT ---
        ($this->useCase)($request);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testItHandlesClientLoginSuccessfully(): void
    {
        // --- ARRANGE ---
        $email = 'client.final@gmail.com';
        $request = new LoginUserRequest(email: $email);

        $clientMock = Client::initiate(
            email: $email,
            firstName: 'Jean',
            lastName: 'Dupont',
        );

        // 1. Le repository CGP ne trouve personne
        $this->userRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        // 2. Le repository Client trouve le client final
        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($clientMock);

        // 3. Rotation de token sur le client
        // 4. Persistance
        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($clientMock);

        // 5. Assertion sur le message Messenger
        $this->messageBusMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (SendMagicLinkMessage $message) use ($email, $clientMock): bool {
                $this->assertSame($email, $message->email);
                $this->assertSame($clientMock->magicLinkToken, $message->magicLinkToken);
                $this->assertSame(UserType::CLIENT, $message->recipientType);

                return true;
            }))
            ->willReturnCallback(static fn (SendMagicLinkMessage $msg): Envelope => new Envelope($msg));

        // --- ACT ---
        ($this->useCase)($request);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testItHandlesAdminLoginSuccessfully(): void
    {
        // --- ARRANGE ---
        $email = 'admin.final@gmail.com';
        $request = new LoginUserRequest(email: $email);

        $adminMock = Admin::initiate(
            email: $email,
            firstName: 'Eric',
            lastName: 'Pastille',
        );

        // 1. Le repository CGP ne trouve personne
        $this->userRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        // 2. Le repository Client trouve le client final
        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->adminRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($adminMock);

        // 3. Rotation de token sur le client
        // 4. Persistance
        $this->adminRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($adminMock);

        // 5. Assertion sur le message Messenger
        $this->messageBusMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (SendMagicLinkMessage $message) use ($email, $adminMock): bool {
                $this->assertSame($email, $message->email);
                $this->assertSame($adminMock->magicLinkToken, $message->magicLinkToken);
                $this->assertSame(UserType::ADMIN, $message->recipientType);

                return true;
            }))
            ->willReturnCallback(static fn (SendMagicLinkMessage $msg): Envelope => new Envelope($msg));

        // --- ACT ---
        ($this->useCase)($request);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testItThrowsUserNotFoundExceptionWhenEmailDoesNotExist(): void
    {
        // --- ARRANGE ---
        $email = 'unknown@nobody.com';
        $request = new LoginUserRequest(email: $email);

        $this->userRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->adminRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        // Aucun message ne doit être envoyé si l'email est introuvable
        $this->messageBusMock
            ->expects($this->never())
            ->method('dispatch');

        // --- EXPECT EXCEPTION ---
        $this->expectException(UserNotFoundException::class);

        // --- ACT ---
        ($this->useCase)($request);
    }
}
