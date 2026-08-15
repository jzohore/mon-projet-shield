<?php

declare(strict_types=1);

namespace App\Tests\Application\User\Login;

use App\Application\User\DTO\Request\LoginUserRequest;
use App\Application\User\UseCase\SendLoginUserUseCase;
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
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class SendLoginUserUseCaseTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private ClientRepositoryInterface&MockObject $clientRepository;
    private AdminRepositoryInterface&MockObject $adminRepository;
    private MessageBusInterface&MockObject $messageBus;
    private LoggerInterface&MockObject $securityLogger;
    private SendLoginUserUseCase $useCase;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $this->adminRepository = $this->createMock(AdminRepositoryInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->securityLogger = $this->createMock(LoggerInterface::class);

        $this->useCase = new SendLoginUserUseCase(
            $this->userRepository,
            $this->messageBus,
            $this->clientRepository,
            $this->adminRepository,
            $this->securityLogger
        );
    }

    /**
     * 🛡️ Helper DDD : Crée une VRAIE instance pour tester la mutation interne du token.
     */
    private function createRealUser(string $email): User
    {
        $reflection = new \ReflectionClass(User::class);
        /** @var User $user */
        $user = $reflection->newInstanceWithoutConstructor();

        // 1. Injection de l'email
        $reflection->getProperty('email')->setValue($user, $email);

        // 2. 🛡️ FIX : Injection d'un ID factice pour satisfaire la Guard Clause du logger
        $idProperty = $reflection->getProperty('id');

        // On vérifie dynamiquement le type attendu par ta propriété $id (Uuid ou Ulid)
        // pour ne pas déclencher une TypeError PHP 8.4
        $idTypeName = $idProperty->getType()?->getName() ?? '';

        if (str_contains($idTypeName, 'Ulid')) {
            $fakeId = new \Symfony\Component\Uid\Ulid();
        } else {
            // Par défaut, on part du principe que tu utilises un Uuid v4
            $fakeId = \Symfony\Component\Uid\Uuid::v4();
        }

        $idProperty->setValue($user, $fakeId);

        return $user;
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionOnInvalidEmailFormat(): void
    {
        // Arrange
        $request = new LoginUserRequest();
        $request->email = 'not-an-email';

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le format de l\'adresse email est invalide.');

        // Act
        ($this->useCase)($request);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testLogsWarningAndThrowsExceptionIfUserNotFound(): void
    {
        // Arrange
        $request = new LoginUserRequest();
        $request->email = 'ghost@kysure.fr';

        $this->userRepository->method('findByEmail')->willReturn(null);
        $this->clientRepository->method('findByEmail')->willReturn(null);
        $this->adminRepository->method('findByEmail')->willReturn(null);

        // Assert 1 : Le log de démarrage est bien enregistré
        $this->securityLogger->expects($this->once())
            ->method('info')
            ->with('Tentative de connexion Magic Link initiée', ['email' => 'ghost@kysure.fr']);

        // Assert 2 : 🛡️ SECOPS - Le log d'alerte (brute-force/énumération) est déclenché
        $this->securityLogger->expects($this->once())
            ->method('warning')
            ->with('Tentative de connexion échouée : email introuvable', ['email' => 'ghost@kysure.fr']);

        // Assert 3 : L'exception est levée
        $this->expectException(UserNotFoundException::class);

        // Act
        ($this->useCase)($request);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testSuccessfullyProcessesCgpLogin(): void
    {
        // Arrange
        $request = new LoginUserRequest();
        // Test de la frugalité : on vérifie que le UseCase nettoie l'input (espaces + majuscules)
        $request->email = '  CGP@kysure.fr  ';

        $cleanedEmail = 'cgp@kysure.fr';
        $user = $this->createRealUser($cleanedEmail);

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($cleanedEmail)
            ->willReturn($user);

        // Stub du MessageBus pour renvoyer une Envelope (Requis par l'interface Symfony)
        $this->messageBus->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        // Assert 1 : Sauvegarde du User avec son token muté
        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (User $savedUser): bool => !empty($savedUser->magicLinkToken)));

        // Assert 2 : Dispatch du message asynchrone avec le bon Type
        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn ($message): bool => $message instanceof SendMagicLinkMessage
                && $message->email === $cleanedEmail
                && UserType::CGP === $message->recipientType
                && !empty($message->magicLinkToken)))
            ->willReturn(new Envelope(new \stdClass()));

        // Assert 3 : Le log de succès est enregistré
        $this->securityLogger->expects($this->exactly(2)) // 1x init, 1x success
        ->method('info');

        // Act
        ($this->useCase)($request);
    }
}
