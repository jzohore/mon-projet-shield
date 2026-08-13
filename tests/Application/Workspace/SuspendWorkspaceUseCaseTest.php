<?php

declare(strict_types=1);

namespace App\Tests\Application\Workspace;

use App\Application\Workspace\DTO\Request\SuspendWorkspaceRequest;
use App\Application\Workspace\UseCase\SuspendWorkspaceUseCase;
use App\Domain\User\Entity\Admin;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Event\WorkspaceSuspendedEvent;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class SuspendWorkspaceUseCaseTest extends TestCase
{
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private WorkspaceRepositoryInterface&MockObject $workspaceRepository;
    private SuspendWorkspaceUseCase $useCase;

    protected function setUp(): void
    {
        // 1. Arrange : Isoler le UseCase de l'infrastructure
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->workspaceRepository = $this->createMock(WorkspaceRepositoryInterface::class);

        $this->useCase = new SuspendWorkspaceUseCase(
            $this->eventDispatcher,
            $this->workspaceRepository
        );
    }

    /**
     * 🛡️ SECOPS / DDD : Helper pour instancier un Workspace verrouillé.
     */
    private function createWorkspaceState(bool $isActive = true, ?string $suspensionReason = null): Workspace
    {
        $reflection = new \ReflectionClass(Workspace::class);

        /** @var Workspace $workspace */
        $workspace = $reflection->newInstanceWithoutConstructor();

        $reflection->getProperty('isActive')->setValue($workspace, $isActive);
        $reflection->getProperty('suspensionReason')->setValue($workspace, $suspensionReason);

        return $workspace;
    }

    /**
     * Helper pour simuler l'Administrateur KYSURE.
     */
    private function createAdminMock(string $email, string $fullName): Admin
    {
        $admin = $this->createMock(Admin::class);
        $admin->method('getUserIdentifier')->willReturn($email);
        $admin->method('getFullName')->willReturn($fullName);

        return $admin;
    }

    public function testInvokeSuccessfullySuspendsWorkspaceAndDispatchesEvent(): void
    {
        // Arrange
        $workspace = $this->createWorkspaceState(isActive: true);

        $request = new SuspendWorkspaceRequest(
            suspensionReason: 'Défaut de paiement Stripe prolongé.',
            deletedByEmail: 'admin@kysure.fr',
            deletedByFullName: 'Admin Kysure'
        );

        // Assert : On vérifie que la persistance est appelée
        $this->workspaceRepository->expects($this->once())
            ->method('save')
            ->with($workspace);

        // Assert : On vérifie que l'événement LCB-FT est dispatché avec les bonnes informations
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (WorkspaceSuspendedEvent $event): bool => $event->workspace === $workspace
                && $event->email === $request->deletedByEmail
                && $event->fullName === $request->deletedByFullName));

        // Act
        ($this->useCase)($workspace, $request);

        // Assert additionnel : L'état interne de l'entité a bien été muté
        $this->assertFalse($workspace->isActive);
        $this->assertSame('Défaut de paiement Stripe prolongé.', $workspace->suspensionReason);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testSuspendWorkspaceRequestFactoryMethodHydratesCorrectly(): void
    {
        // Arrange
        $workspace = $this->createWorkspaceState(
            isActive: false,
            suspensionReason: 'Fraude suspectée par l\'ACPR.'
        );
        $admin = $this->createAdminMock('compliance@kysure.fr', 'Responsable Conformité');

        // Act
        $dto = SuspendWorkspaceRequest::fromEntity($workspace, $admin);

        // Assert
        $this->assertSame('Fraude suspectée par l\'ACPR.', $dto->suspensionReason);
        $this->assertSame('compliance@kysure.fr', $dto->deletedByEmail);
        $this->assertSame('Responsable Conformité', $dto->deletedByFullName);
    }
}
