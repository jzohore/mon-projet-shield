<?php

declare(strict_types=1);

namespace App\Tests\Application\Workspace;

use App\Application\Workspace\DTO\Request\EditWorkspaceRequest;
use App\Application\Workspace\UseCase\EditWorkspaceUseCase;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Event\WorkspaceUpdatedEvent;
use App\Domain\Workspace\Exception\WorkspaceNameAlreadyExistsException;
use App\Domain\Workspace\Exception\WorkspaceSiretAlreadyExistsException;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class EditWorkspaceUseCaseTest extends TestCase
{
    private WorkspaceRepositoryInterface&MockObject $workspaceRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private EditWorkspaceUseCase $useCase;

    protected function setUp(): void
    {
        // 1. Arrange global : Création des mocks pour isoler le test de l'infrastructure
        $this->workspaceRepository = $this->createMock(WorkspaceRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->useCase = new EditWorkspaceUseCase(
            $this->workspaceRepository,
            $this->eventDispatcher
        );
    }

    /**
     * 🛡️ SECOPS / DDD : Helper pour instancier un Workspace verrouillé
     * sans déclencher son constructeur privé ni requérir toutes ses dépendances.
     */
    private function createWorkspaceState(string $name, string $siret): Workspace
    {
        $reflection = new \ReflectionClass(Workspace::class);

        /** @var Workspace $workspace */
        $workspace = $reflection->newInstanceWithoutConstructor();

        // Hydratation via réflexion (contourne le private(set) pour le setup du test)
        $reflection->getProperty('name')->setValue($workspace, $name);
        $reflection->getProperty('siret')->setValue($workspace, $siret);

        return $workspace;
    }

    public function testInvokeSuccessfullyUpdatesWorkspaceAndDispatchesEvent(): void
    {
        // Arrange
        $workspace = $this->createWorkspaceState('Ancien Nom', '11111111111111');

        $request = new EditWorkspaceRequest();
        $request->name = 'Nouveau Nom';
        $request->siret = '22222222222222';
        $request->updatedByEmail = 'admin@kysure.fr';
        $request->updatedByFullName = 'Admin Kysure';

        $this->workspaceRepository->expects($this->once())
            ->method('existsByName')
            ->willReturn(false);

        $this->workspaceRepository->expects($this->once())
            ->method('existsBySiret')
            ->willReturn(false);

        $this->workspaceRepository->expects($this->once())
            ->method('save')
            ->with($workspace);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(WorkspaceUpdatedEvent::class));

        // Act
        ($this->useCase)($workspace, $request);

        // Assert
        $this->assertSame('Nouveau Nom', $workspace->name);
        $this->assertSame('22222222222222', $workspace->siret);
    }

    public function testInvokeDoesNothingWhenNoChangesAreMade(): void
    {
        // Arrange
        $workspace = $this->createWorkspaceState('Mon Cabinet', '12345678901234');

        $request = new EditWorkspaceRequest();
        $request->name = 'Mon Cabinet';
        $request->siret = '12345678901234';
        $request->updatedByEmail = 'admin@kysure.fr';
        $request->updatedByFullName = 'Admin Kysure';

        $this->workspaceRepository->expects($this->never())->method('existsByName');
        $this->workspaceRepository->expects($this->never())->method('existsBySiret');
        $this->workspaceRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        // Act
        ($this->useCase)($workspace, $request);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testInvokeThrowsExceptionWhenNameAlreadyExists(): void
    {
        // Arrange
        $workspace = $this->createWorkspaceState('Ancien Nom', '11111111111111');

        $request = new EditWorkspaceRequest();
        $request->name = 'Nom Déjà Pris';
        $request->siret = '11111111111111';

        $this->workspaceRepository->expects($this->once())
            ->method('existsByName')
            ->willReturn(true);

        $this->expectException(WorkspaceNameAlreadyExistsException::class);

        // Act
        ($this->useCase)($workspace, $request);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testInvokeThrowsExceptionWhenSiretAlreadyExists(): void
    {
        // Arrange
        $workspace = $this->createWorkspaceState('Cabinet A', '11111111111111');

        $request = new EditWorkspaceRequest();
        $request->name = 'Cabinet A';
        $request->siret = '99999999999999';

        $this->workspaceRepository->expects($this->never())->method('existsByName');
        $this->workspaceRepository->expects($this->once())
            ->method('existsBySiret')
            ->willReturn(true);

        $this->expectException(WorkspaceSiretAlreadyExistsException::class);

        // Act
        ($this->useCase)($workspace, $request);
    }
}
