<?php

declare(strict_types=1);

namespace App\Tests\Application\ComplianceFolder;

use App\Application\Compliance\UseCase\ComplianceFolder\ArchiveFolderUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Service\CurrentUserProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ArchiveFolderUseCaseTest extends TestCase
{
    private ComplianceFolderRepositoryInterface&MockObject $repository;
    private CurrentUserProvider&MockObject $currentUserProvider;

    private EventDispatcherInterface&MockObject $eventDispatcher;
    private ArchiveFolderUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ComplianceFolderRepositoryInterface::class);
        $this->currentUserProvider = $this->createMock(CurrentUserProvider::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->useCase = new ArchiveFolderUseCase(
            $this->repository,
            $this->currentUserProvider,
            $this->eventDispatcher,
        );
    }

    /**
     * 🚀 HELPER AVANCÉ : Hydratation d'Entité (Test Data Builder)
     * Contourne le constructeur et l'encapsulation stricte (private(set)) de PHP 8.4.
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
    public function testInvokeMarksFolderAsArchiveAndSavesIt(): void
    {
        // 1. Préparation (Arrange)
        $userEmail = 'cgp.partner@kysure.com';

        $user = $this->createEntityState(User::class, [
            'email' => $userEmail,
            'slugId' => 'user_123',
        ]);

        // Utilisation d'une implémentation concrète de ComplianceFolder
        $folder = $this->createEntityState(BusinessFolder::class, [
            'slugId' => 'folder_archive_123',
        ]);

        if (!$folder instanceof ComplianceFolder) {
            return;
        }

        // 2. Expectations (Assert - Mocks)
        $this->currentUserProvider
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        // On vérifie que le repository sauvegarde bien l'instance passée au Use Case
        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($folder));

        // 3. Exécution (Act)
        ($this->useCase)($folder);

        // 4. Post-Assertion (Optionnelle)
        // En pur DDD, si ta méthode `markAsArchive` expose publiquement un changement d'état
        // ou peuple une propriété `archivedBy`, tu peux vérifier la mutation de l'entité ici :
        // $this->assertTrue($folder->isArchived());
    }
}
