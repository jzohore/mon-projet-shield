<?php

declare(strict_types=1);

namespace App\Tests\Application\ComplianceFolder;

use App\Application\Compliance\UseCase\ComplianceFolder\MarkAsAcceptedRecordingUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Event\AcceptedRecordingEvent;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Service\CurrentUserProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class MarkAsAcceptedRecordingUseCaseTest extends TestCase
{
    private ComplianceFolderRepositoryInterface&MockObject $repository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private CurrentUserProvider&MockObject $currentUserProvider;
    private MarkAsAcceptedRecordingUseCase $useCase;

    private const string FOLDER_SLUG = 'folder_rec_123';
    private const string USER_SLUG = 'usr_abc_789';

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ComplianceFolderRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->currentUserProvider = $this->createMock(CurrentUserProvider::class);

        $this->useCase = new MarkAsAcceptedRecordingUseCase(
            $this->repository,
            $this->eventDispatcher,
            $this->currentUserProvider
        );
    }

    /**
     * 🚀 HELPER AVANCÉ : Hydratation d'Entité (Test Data Builder).
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

    public function testInvokeMarksFolderSavesAndDispatchesEvent(): void
    {
        // 1. Préparation (Arrange)
        // Utilisation d'entités concrètes (BusinessFolder hérite de ComplianceFolder)
        $folder = $this->createEntityState(BusinessFolder::class, [
            'slugId' => self::FOLDER_SLUG,
        ]);

        if (!$folder instanceof ComplianceFolder) {
            return;
        }

        $user = $this->createEntityState(User::class, [
            'slugId' => self::USER_SLUG,
        ]);

        // 2. Expectations (Assert - Mocks)
        $this->currentUserProvider
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        // On vérifie que le repository reçoit bien notre instance concrète mutée
        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($folder));

        // 🛡️ Vérification de la création correcte de l'Event DTO
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (mixed $event): bool => $event instanceof AcceptedRecordingEvent
                && self::FOLDER_SLUG === $event->folderSlugId
                && self::USER_SLUG === $event->userSlugId))
            // Le dispatcher de Symfony retourne toujours l'événement passé en argument
            ->willReturnArgument(0);

        // 3. Exécution (Act)
        ($this->useCase)($folder);

        // 4. Post-Assertion
        // Si tu as un getter ou une propriété lisible qui change via markAsRecording(),
        // tu pourrais idéalement rajouter une assertion ici pour vérifier que l'état a bien changé.
        // Exemple: $this->assertTrue($folder->isRecordingStatus());
    }
}
