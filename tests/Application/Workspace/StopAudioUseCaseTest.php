<?php

declare(strict_types=1);

namespace App\Tests\Application\Workspace;

use App\Application\Workspace\UseCase\StopAudioUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Exception\ComplianceFolderNotFoundException;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Infrastructure\Compliance\Message\FinalizeMeetingAudioMessage;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class StopAudioUseCaseTest extends TestCase
{
    private ComplianceFolderRepositoryInterface&MockObject $folderRepository;
    private MessageBusInterface&MockObject $messageBus;
    private StopAudioUseCase $useCase;

    private const string VALID_SLUG = 'folder_123';
    private const string VALID_SESSION_ID = '123e4567-e89b-12d3-a456-426614174000';
    private const int CONSUMED_SECONDS = 42;

    protected function setUp(): void
    {
        $this->folderRepository = $this->createMock(ComplianceFolderRepositoryInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);

        $this->useCase = new StopAudioUseCase(
            $this->folderRepository,
            $this->messageBus
        );
    }

    /**
     * 🚀 HELPER AVANCÉ : Hydratation d'Entité (Test Data Builder)
     * Contourne le constructeur pour instancier des entités pures sans utiliser de mocks PHPUnit.
     */
    private function createEntityState(string $className, array $properties = []): object
    {
        $reflection = new \ReflectionClass($className);
        $entity = $reflection->newInstanceWithoutConstructor();

        foreach ($properties as $propertyName => $value) {
            $property = $reflection->getProperty($propertyName);
            $declaringProperty = $property->getDeclaringClass()->getProperty($propertyName);
            $declaringProperty->setValue($entity, $value);
        }

        return $entity;
    }

    public function testExecuteThrowsExceptionWhenFolderNotFound(): void
    {
        $this->folderRepository
            ->expects($this->once())
            ->method('findOneBySlugId')
            ->with(self::VALID_SLUG)
            ->willReturn(null);

        // Le messageBus ne DOIT JAMAIS être appelé en cas d'erreur
        $this->messageBus->expects($this->never())->method('dispatch');

        try {
            ($this->useCase)(self::VALID_SLUG, self::VALID_SESSION_ID, self::CONSUMED_SECONDS);

            $this->fail('Une ComplianceFolderNotFoundException aurait dû être levée.');
        } catch (ComplianceFolderNotFoundException $e) {
            // Si tu as personnalisé le message de ton exception métier, tu peux le vérifier ici
            $this->assertInstanceOf(ComplianceFolderNotFoundException::class, $e);
        }
    }

    public function testExecuteThrowsExceptionWhenWorkspaceRefusesRecording(): void
    {
        // On mock le Workspace pour simuler le rejet métier (Kill-Switch ou Quota S3/IA)
        $workspaceMock = $this->createMock(Workspace::class);
        $workspaceMock->expects($this->once())
            ->method('assertMeetingRecordingIsAllowed')
            ->willThrowException(new \DomainException('Solde de minutes épuisé.'));

        // Création de l'entité concrète via notre Helper
        $folder = $this->createEntityState(BusinessFolder::class, [
            'workspace' => $workspaceMock,
        ]);

        $this->folderRepository
            ->expects($this->once())
            ->method('findOneBySlugId')
            ->with(self::VALID_SLUG)
            ->willReturn($folder);

        $this->messageBus->expects($this->never())->method('dispatch');

        try {
            ($this->useCase)(self::VALID_SLUG, self::VALID_SESSION_ID, self::CONSUMED_SECONDS);

            $this->fail('Une DomainException aurait dû être levée par le Workspace.');
        } catch (\DomainException $e) {
            $this->assertSame('Solde de minutes épuisé.', $e->getMessage());
        }
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteDispatchesFinalizeMeetingAudioMessageOnSuccess(): void
    {
        $workspaceMock = $this->createMock(Workspace::class);
        $workspaceMock->expects($this->once())->method('assertMeetingRecordingIsAllowed');

        $folder = $this->createEntityState(BusinessFolder::class, [
            'workspace' => $workspaceMock,
        ]);

        $this->folderRepository->method('findOneBySlugId')->willReturn($folder);

        // 🚀 VERIFICATION DU DTO : On s'assure que le UseCase construit le bon message asynchrone
        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (mixed $message): bool => $message instanceof FinalizeMeetingAudioMessage
                && self::VALID_SLUG === $message->folderSlugId
                && self::VALID_SESSION_ID === $message->sessionId
                && self::CONSUMED_SECONDS === $message->consumedSeconds))
            // MessageBusInterface::dispatch retourne une Envelope
            ->willReturn(new Envelope(new FinalizeMeetingAudioMessage(self::VALID_SLUG, self::VALID_SESSION_ID, self::CONSUMED_SECONDS)));

        // Exécution du Use Case
        ($this->useCase)(self::VALID_SLUG, self::VALID_SESSION_ID, self::CONSUMED_SECONDS);
    }
}
