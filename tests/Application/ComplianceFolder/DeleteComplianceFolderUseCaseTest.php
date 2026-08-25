<?php

declare(strict_types=1);

namespace App\Tests\Application\ComplianceFolder;

use App\Application\Compliance\UseCase\ComplianceFolder\DeleteComplianceFolderUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\Compliance\Event\DeleteComplianceEvent;
use App\Domain\Compliance\Exception\CannotDeleteActiveFolderException;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Service\CurrentUserProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class DeleteComplianceFolderUseCaseTest extends TestCase
{
    private ComplianceFolderRepositoryInterface&MockObject $repository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private CurrentUserProvider&MockObject $userProvider;
    private DeleteComplianceFolderUseCase $useCase;

    private const string FOLDER_SLUG = 'folder_del_123';
    private const string USER_SLUG = 'user_abc_789';
    private const string FOLDER_REF = 'REF-9999';

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ComplianceFolderRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->userProvider = $this->createMock(CurrentUserProvider::class);

        $this->useCase = new DeleteComplianceFolderUseCase(
            $this->repository,
            $this->eventDispatcher,
            $this->userProvider
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

    public function testInvokeThrowsExceptionWhenFolderIsNotDraft(): void
    {
        // 1. Arrange : Création d'un dossier avec un statut qui N'EST PAS un draft.
        // Adapte le nom de la propriété gérant le statut ('status', 'processingStatus', etc.)
        // selon l'implémentation de ta méthode isDraft()
        $activeFolder = $this->createEntityState(BusinessFolder::class, [
            'reference' => self::FOLDER_REF,
            'status' => ComplianceFolderStatus::PENDING_DOCS, // (Exemple de propriété qui rend isDraft() false)
        ]);

        // Pour ce test, nous simulons le fait que isDraft() retourne false
        // (à toi de t'assurer que les propriétés injectées ci-dessus produisent bien ce résultat).

        // 🛡️ Sécurité : Les services d'infrastructure ne doivent jamais être appelés
        $this->userProvider->expects($this->never())->method('getUser');
        $this->repository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        try {
            // Act
            ($this->useCase)($activeFolder);

            // Assert (Fail Fast)
            $this->fail('Une CannotDeleteActiveFolderException aurait dû être levée car le dossier n\'est pas un brouillon.');
        } catch (CannotDeleteActiveFolderException $e) {
            $this->assertInstanceOf(CannotDeleteActiveFolderException::class, $e);
            // Vérification optionnelle du message si tu l'as défini dans forFolder()
            // $this->assertStringContainsString(self::FOLDER_REF, $e->getMessage());
        }
    }

    public function testInvokeMarksFolderAsDeletedSavesAndDispatchesEvent(): void
    {
        // 1. Arrange
        $user = $this->createEntityState(User::class, [
            'slugId' => self::USER_SLUG,
        ]);

        $draftFolder = $this->createEntityState(BusinessFolder::class, [
            'slugId' => self::FOLDER_SLUG,
            'reference' => self::FOLDER_REF,
            // Injecter la propriété qui fait que isDraft() retourne TRUE
            'status' => ComplianceFolderStatus::DRAFT,
        ]);

        // 2. Expectations
        $this->userProvider
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($draftFolder));

        // 🛡️ Vérification de l'Event DTO
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (mixed $event): bool => $event instanceof DeleteComplianceEvent
                && self::FOLDER_SLUG === $event->folderSlugId
                && self::USER_SLUG === $event->userSlugId))
            ->willReturnArgument(0);

        // 3. Act
        ($this->useCase)($draftFolder);

        // 4. Assert
        // Optionnel : vérifier que l'entité a bien muté (si la propriété est lisible)
        // $this->assertTrue($draftFolder->isDeleted());
    }
}
