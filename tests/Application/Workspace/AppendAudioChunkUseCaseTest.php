<?php

declare(strict_types=1);

namespace App\Tests\Application\Workspace;

use App\Application\Workspace\UseCase\AppendAudioChunkUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Port\DocumentStorageInterface;
use App\Domain\Workspace\Entity\Workspace;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class AppendAudioChunkUseCaseTest extends TestCase
{
    private ComplianceFolderRepositoryInterface&MockObject $folderRepository;
    private DocumentStorageInterface&MockObject $storage;
    private AppendAudioChunkUseCase $useCase;

    private const string VALID_SLUG = 'folder_123';
    private const string VALID_SESSION_ID = '123e4567-e89b-12d3-a456-426614174000';

    protected function setUp(): void
    {
        $this->folderRepository = $this->createMock(ComplianceFolderRepositoryInterface::class);
        $this->storage = $this->createMock(DocumentStorageInterface::class);

        $this->useCase = new AppendAudioChunkUseCase(
            $this->folderRepository,
            $this->storage
        );
    }

    /**
     * 🚀 TON APPROCHE (Test Data Builder) :
     * Crée une vraie instance d'Entité en contournant le constructeur et les règles de visibilité.
     * 100% DDD et insensible aux limitations des Mocks de PHPUnit.
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

    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteThrowsExceptionWhenFolderNotFound(): void
    {
        $this->folderRepository
            ->expects($this->once())
            ->method('findOneBySlugId')
            ->with(self::VALID_SLUG)
            ->willReturn(null);

        $chunk = $this->createMock(UploadedFile::class);

        try {
            $this->useCase->execute(self::VALID_SLUG, self::VALID_SESSION_ID, $chunk, 0);
            $this->fail('Une InvalidArgumentException aurait dû être levée car le dossier n\'existe pas.');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('Dossier introuvable.', $e->getMessage());
        }
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteThrowsExceptionWhenWorkspaceRefusesRecording(): void
    {
        // On mock exceptionnellement le Workspace ici car on veut forcer son comportement
        // métier (le throw) sans avoir à deviner sa logique interne de calcul de quota.
        $workspaceMock = $this->createMock(Workspace::class);
        $workspaceMock->expects($this->once())
            ->method('assertMeetingRecordingIsAllowed')
            ->willThrowException(new \DomainException('Solde épuisé.'));

        // 🚀 Utilisation de ta méthode : Vraie entité ComplianceFolder !
        $folder = $this->createEntityState(BusinessFolder::class, [
            'slugId' => self::VALID_SLUG,
            'workspace' => $workspaceMock,
            'audioMimeType' => null,
        ]);

        $this->folderRepository->method('findOneBySlugId')->willReturn($folder);
        $chunk = $this->createMock(UploadedFile::class);

        try {
            $this->useCase->execute(self::VALID_SLUG, self::VALID_SESSION_ID, $chunk, 0);
            $this->fail('Une DomainException aurait dû être levée à cause du quota du Workspace.');
        } catch (\DomainException $e) {
            $this->assertSame('Solde épuisé.', $e->getMessage());
        }
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteStoresChunkAndUpdatesMimeTypeOnce(): void
    {
        $workspaceMock = $this->createMock(Workspace::class);
        $workspaceMock->expects($this->once())->method('assertMeetingRecordingIsAllowed');

        // 🚀 Vraie entité avec état initial
        $folder = $this->createEntityState(BusinessFolder::class, [
            'slugId' => self::VALID_SLUG,
            'workspace' => $workspaceMock,
            'audioMimeType' => null,
        ]);

        $this->folderRepository->method('findOneBySlugId')->willReturn($folder);

        // On s'attend à ce que le repository sauvegarde l'entité car le mimeType aura changé
        $this->folderRepository->expects($this->once())->method('save')->with($folder);

        $chunk = $this->createMock(UploadedFile::class);
        $expectedPath = sprintf('tmp/meetings/%s/%s/chunks', self::VALID_SLUG, self::VALID_SESSION_ID);

        $this->storage->expects($this->once())
            ->method('store')
            ->with($chunk, $expectedPath, '000005.chunk');

        $this->useCase->execute(self::VALID_SLUG, self::VALID_SESSION_ID, $chunk, 5, 'audio/webm');

        // 🛡️ Vrai test DDD : on vérifie que l'entité RÉELLE a bien muté comme prévu
        $this->assertSame('audio/webm', $folder->audioMimeType);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testExecuteStoresChunkWithoutUpdatingMimeTypeIfAlreadySet(): void
    {
        $workspaceMock = $this->createMock(Workspace::class);
        $workspaceMock->expects($this->once())->method('assertMeetingRecordingIsAllowed');

        // 🚀 Vraie entité avec mimeType déjà défini
        $folder = $this->createEntityState(BusinessFolder::class, [
            'slugId' => self::VALID_SLUG,
            'workspace' => $workspaceMock,
            'audioMimeType' => 'audio/mp4',
        ]);

        $this->folderRepository->method('findOneBySlugId')->willReturn($folder);

        // Le repository ne DOIT PAS être appelé (Idempotence)
        $this->folderRepository->expects($this->never())->method('save');

        $chunk = $this->createMock(UploadedFile::class);
        $this->storage->expects($this->once())->method('store');

        $this->useCase->execute(self::VALID_SLUG, self::VALID_SESSION_ID, $chunk, 6, 'audio/mp4');

        // Le type n'a pas dû bouger
        $this->assertSame('audio/mp4', $folder->audioMimeType);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testChunksDirectoryFailsWithInvalidSlug(): void
    {
        try {
            AppendAudioChunkUseCase::chunksDirectory('dossier invalide!', self::VALID_SESSION_ID);
            $this->fail('Exception attendue.');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('Identifiant de dossier invalide.', $e->getMessage());
        }
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testChunksDirectoryFailsWithInvalidUuid(): void
    {
        try {
            AppendAudioChunkUseCase::chunksDirectory(self::VALID_SLUG, 'not-a-uuid');
            $this->fail('Exception attendue.');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('Format de session_id invalide.', $e->getMessage());
        }
    }
}
