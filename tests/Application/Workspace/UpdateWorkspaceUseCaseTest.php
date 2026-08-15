<?php

declare(strict_types=1);

namespace App\Tests\Application\Workspace;

use App\Application\Workspace\DTO\Request\UpdateWorkspaceRequest;
use App\Application\Workspace\UseCase\UpdateInfoWorkspaceUseCase;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Enum\Industry;
use App\Domain\Workspace\Event\WorkspaceUpdatedEvent;
use App\Domain\Workspace\Exception\WorkspaceNameAlreadyExistsException;
use App\Domain\Workspace\Exception\WorkspaceSirenAlreadyExistsException;
use App\Domain\Workspace\Exception\WorkspaceSiretAlreadyExistsException;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class UpdateWorkspaceUseCaseTest extends TestCase
{
    private WorkspaceRepositoryInterface&MockObject $workspaceRepository;
    private CurrentWorkspaceProvider&MockObject $currentWorkspaceProvider;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private CurrentUserProvider&MockObject $currentUserProvider;
    private UpdateInfoWorkspaceUseCase $useCase;

    protected function setUp(): void
    {
        $this->workspaceRepository = $this->createMock(WorkspaceRepositoryInterface::class);
        $this->currentWorkspaceProvider = $this->createMock(CurrentWorkspaceProvider::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->currentUserProvider = $this->createMock(CurrentUserProvider::class);

        $this->useCase = new UpdateInfoWorkspaceUseCase(
            $this->workspaceRepository,
            $this->currentWorkspaceProvider,
            $this->eventDispatcher,
            $this->currentUserProvider
        );
    }

    /**
     * 🛡️ Helper pour simuler l'état actuel du Workspace en base.
     */
    private function createMockWorkspace(
        string $name = 'KYSURE Original',
        ?string $siren = '111111111',
        ?string $siret = '11111111100012',
        ?string $address = 'Paris',
        Industry $industry = Industry::OTHER,
    ): Workspace {
        $reflection = new \ReflectionClass(Workspace::class);
        /** @var Workspace $workspace */
        $workspace = $reflection->newInstanceWithoutConstructor();

        $reflection->getProperty('name')->setValue($workspace, $name);
        $reflection->getProperty('siren')->setValue($workspace, $siren);
        $reflection->getProperty('siret')->setValue($workspace, $siret);
        $reflection->getProperty('address')->setValue($workspace, $address);
        $reflection->getProperty('industry')->setValue($workspace, $industry);

        return $workspace;
    }

    private function createMockUser(string $email = 'admin@kysure.fr', string $fullName = 'Admin Kysure'): User
    {
        // 1. On mocke l'objet pour pouvoir surcharger ses méthodes complexes (comme getFullName)
        $user = $this->createMock(User::class);
        $user->method('getFullName')->willReturn($fullName);

        // 2. 🛡️ SECOPS : Comme $email est une propriété "public private(set)" en PHP 8.4
        // et non une méthode, on utilise la Réflexion pour forcer sa valeur dans le mock.
        $reflection = new \ReflectionClass(User::class);
        $reflection->getProperty('email')->setValue($user, $email);

        return $user;
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionIfNameIsChangedAndAlreadyExists(): void
    {
        // Arrange
        $workspace = $this->createMockWorkspace();
        $this->currentWorkspaceProvider->method('getWorkspace')->willReturn($workspace);
        $this->currentUserProvider->method('getUser')->willReturn($this->createMockUser());

        $request = new UpdateWorkspaceRequest();
        $request->name = 'Nouveau Nom Déjà Pris';
        $request->siren = '111111111'; // Inchangé
        $request->siret = '11111111100012'; // Inchangé

        $this->workspaceRepository->expects($this->once())
            ->method('existsByName')
            ->with('Nouveau Nom Déjà Pris')
            ->willReturn(true);

        // Assert
        $this->expectException(WorkspaceNameAlreadyExistsException::class);

        // Act
        ($this->useCase)($request);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionIfSiretIsChangedAndAlreadyExists(): void
    {
        // Arrange
        $workspace = $this->createMockWorkspace();
        $this->currentWorkspaceProvider->method('getWorkspace')->willReturn($workspace);
        $this->currentUserProvider->method('getUser')->willReturn($this->createMockUser());

        $request = new UpdateWorkspaceRequest();
        $request->name = 'KYSURE Original'; // Inchangé
        $request->siren = '111111111'; // Inchangé
        $request->siret = '99999999900099'; // Changé

        $this->workspaceRepository->method('existsByName')->willReturn(false);
        $this->workspaceRepository->expects($this->once())
            ->method('existsBySiret')
            ->with('99999999900099')
            ->willReturn(true);

        // Assert
        $this->expectException(WorkspaceSiretAlreadyExistsException::class);

        // Act
        ($this->useCase)($request);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionIfSirenIsChangedAndAlreadyExists(): void
    {
        // Arrange
        $workspace = $this->createMockWorkspace();
        $this->currentWorkspaceProvider->method('getWorkspace')->willReturn($workspace);
        $this->currentUserProvider->method('getUser')->willReturn($this->createMockUser());

        $request = new UpdateWorkspaceRequest();
        $request->name = 'KYSURE Original'; // Inchangé
        $request->siret = '11111111100012'; // Inchangé
        $request->siren = '999999999'; // Changé

        $this->workspaceRepository->method('existsByName')->willReturn(false);
        $this->workspaceRepository->method('existsBySiret')->willReturn(false);
        $this->workspaceRepository->expects($this->once())
            ->method('existsBySiren')
            ->with('999999999')
            ->willReturn(true);

        // Assert
        $this->expectException(WorkspaceSirenAlreadyExistsException::class);

        // Act
        ($this->useCase)($request);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testEarlyReturnIfNothingChanged(): void
    {
        // Arrange
        $workspace = $this->createMockWorkspace(name: 'Identique', siren: '123', siret: '12345', address: 'Paris', industry: Industry::OTHER);
        $this->currentWorkspaceProvider->method('getWorkspace')->willReturn($workspace);
        $this->currentUserProvider->method('getUser')->willReturn($this->createMockUser());

        $request = new UpdateWorkspaceRequest();
        $request->name = 'Identique';
        $request->siren = '123';
        $request->siret = '12345';
        $request->address = 'Paris';
        $request->workspaceIndustry = Industry::OTHER;

        // Assert : On vérifie que les méthodes de persistance et d'événement ne sont JAMAIS appelées
        $this->workspaceRepository->expects($this->never())->method('existsByName');
        $this->workspaceRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        // Act
        ($this->useCase)($request);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testSuccessfullyUpdatesWorkspaceAndDispatchesEvent(): void
    {
        // Arrange
        $workspace = $this->createMockWorkspace(name: 'Ancien Nom', siren: '111111111', siret: '11111111100012');
        $this->currentWorkspaceProvider->method('getWorkspace')->willReturn($workspace);

        $user = $this->createMockUser(email: 'test@kysure.fr', fullName: 'John Doe');
        $this->currentUserProvider->method('getUser')->willReturn($user);

        $request = new UpdateWorkspaceRequest();
        $request->name = 'Nouveau Nom';
        $request->siren = ''; // Test du nettoyage de chaîne vide -> null
        $request->siret = '99999999900099';
        $request->address = 'Lyon';
        $request->workspaceIndustry = Industry::REAL_ESTATE;

        $this->workspaceRepository->method('existsByName')->willReturn(false);
        $this->workspaceRepository->method('existsBySiret')->willReturn(false);
        $this->workspaceRepository->method('existsBySiren')->willReturn(false);

        // Assert 1 : Sauvegarde de l'entité modifiée
        $this->workspaceRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (Workspace $savedWorkspace): bool => 'Nouveau Nom' === $savedWorkspace->name
                && null === $savedWorkspace->siren // La chaîne vide a bien été castée en null
                && '99999999900099' === $savedWorkspace->siret));

        // Assert 2 : Dispatch de l'événement avec les ANCIENNES valeurs pour l'audit LCB-FT
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (WorkspaceUpdatedEvent $event): bool => $event->workspace === $workspace
                && 'Ancien Nom' === $event->oldName
                && '111111111' === $event->oldSiren
                && 'test@kysure.fr' === $event->email
                && 'John Doe' === $event->fullName));

        // Act
        ($this->useCase)($request);
    }
}
