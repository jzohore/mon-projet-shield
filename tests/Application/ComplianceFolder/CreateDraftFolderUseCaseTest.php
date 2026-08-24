<?php

declare(strict_types=1);

namespace App\Tests\Application\ComplianceFolder;

use App\Application\Compliance\DTO\Response\DraftFolderResponse;
use App\Application\Compliance\UseCase\ComplianceFolder\CreateDraftFolderUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Enum\FolderType;
use App\Domain\Compliance\Exception\InvalidFolderTypeException;
use App\Domain\Compliance\Factory\ComplianceFolderFactory;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Compliance\Service\DocumentRequirementEngine;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateDraftFolderUseCaseTest extends TestCase
{
    private ComplianceFolderRepositoryInterface&MockObject $em;
    private CurrentWorkspaceProvider&MockObject $workspaceProvider;
    private ComplianceFolderFactory&MockObject $folderFactory;
    private DocumentRequirementEngine&MockObject $documentRequirementEngine;
    private CurrentUserProvider&MockObject $currentUserProvider;
    private CreateDraftFolderUseCase $useCase;

    protected function setUp(): void
    {
        $this->em = $this->createMock(ComplianceFolderRepositoryInterface::class);
        $this->workspaceProvider = $this->createMock(CurrentWorkspaceProvider::class);
        $this->folderFactory = $this->createMock(ComplianceFolderFactory::class);
        $this->documentRequirementEngine = $this->createMock(DocumentRequirementEngine::class);
        $this->currentUserProvider = $this->createMock(CurrentUserProvider::class);

        $this->useCase = new CreateDraftFolderUseCase(
            $this->em,
            $this->workspaceProvider,
            $this->folderFactory,
            $this->documentRequirementEngine,
            $this->currentUserProvider
        );
    }

    /**
     * 🚀 HELPER AVANCÉ : Hydratation d'Entité (Test Data Builder)
     * Contourne le constructeur et l'encapsulation stricte (private(set)) de PHP 8.4
     * en remontant l'arbre d'héritage pour trouver le scope exact de la propriété.
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
    public function testInvokeThrowsExceptionWhenTypeIsInvalid(): void
    {
        // 🛡️ Fail Fast : On s'assure qu'aucun service n'est appelé si le type est invalide
        $this->currentUserProvider->expects($this->never())->method('getUser');
        $this->workspaceProvider->expects($this->never())->method('getWorkspace');
        $this->folderFactory->expects($this->never())->method('createDraft');

        try {
            ($this->useCase)('type_inconnu_au_bataillon', 'api');
            $this->fail('Une InvalidFolderTypeException aurait dû être levée pour un type invalide.');
        } catch (InvalidFolderTypeException $e) {
            $this->assertInstanceOf(InvalidFolderTypeException::class, $e);
        }
    }

    public function testInvokeCreatesAndSavesDraftFolderSuccessfully(): void
    {
        // 1. Préparation (Arrange)
        // On récupère dynamiquement le premier cas de ton Enum pour éviter que le test ne casse
        // si les valeurs de l'Enum changent à l'avenir.
        $validTypeEnum = FolderType::cases()[0];
        $validTypeRaw = $validTypeEnum->value;
        $method = 'manual';

        // Création de nos vraies entités
        $user = $this->createEntityState(User::class, ['email' => 'cgp@kysure.com']);
        $workspace = $this->createEntityState(Workspace::class);
        $folder = $this->createEntityState(BusinessFolder::class, [
            // On s'assure de définir l'ID ou le slug si DraftFolderResponse::fromEntity en a besoin
            'slugId' => 'folder_test_123',
            'reference' => 'folder_test_123',
        ]);

        // 2. Expectations (Assert - Mocks)
        $this->currentUserProvider->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->workspaceProvider->expects($this->once())
            ->method('getWorkspace')
            ->willReturn($workspace);

        $this->folderFactory->expects($this->once())
            ->method('createDraft')
            ->with($validTypeEnum, $workspace, 'cgp@kysure.com', $method)
            ->willReturn($folder);

        $this->documentRequirementEngine->expects($this->once())
            ->method('generateBaseRequirements')
            ->with($folder);

        $this->em->expects($this->once())
            ->method('save')
            ->with($folder);

        // 3. Exécution (Act)
        $response = ($this->useCase)($validTypeRaw, $method);

        // 4. Vérification du retour final
        $this->assertInstanceOf(DraftFolderResponse::class, $response);
    }
}
