<?php

declare(strict_types=1);

namespace App\Tests\Application\Workspace;

use App\Application\ExternalAPI\Siren\SirenResult;
use App\Application\Workspace\UseCase\VerifySiret\VerifyWorkspaceSiretUseCase;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Event\WorkspaceSiretCheckFailedEvent;
use App\Domain\Workspace\Event\WorkspaceSiretVerifiedEvent;
use App\Domain\Workspace\Exception\WorkspaceNotFoundException;
use App\Domain\Workspace\Gateway\SiretCheckerInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\InvalidArgumentException;

final class VerifyWorkspaceSiretUseCaseTest extends TestCase
{
    private WorkspaceRepositoryInterface&MockObject $workspaceRepository;
    private SiretCheckerInterface&MockObject $siretChecker;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private LoggerInterface&MockObject $logger;
    private VerifyWorkspaceSiretUseCase $useCase;

    protected function setUp(): void
    {
        $this->workspaceRepository = $this->createMock(WorkspaceRepositoryInterface::class);
        $this->siretChecker = $this->createMock(SiretCheckerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->useCase = new VerifyWorkspaceSiretUseCase(
            $this->workspaceRepository,
            $this->siretChecker,
            $this->eventDispatcher,
            $this->logger
        );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenWorkspaceIsNotFound(): void
    {
        $this->workspaceRepository
            ->expects($this->once())
            ->method('findOneBySlug')
            ->with('slug-inconnu')
            ->willReturn(null);

        $this->expectException(WorkspaceNotFoundException::class);

        ($this->useCase)('slug-inconnu', 'admin@kysure.com');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenSiretIsEmpty(): void
    {
        $workspace = $this->createWorkspaceEntity('Cabinet Kysure', '');

        $this->workspaceRepository
            ->expects($this->once())
            ->method('findOneBySlug')
            ->willReturn($workspace);

        // Webmozart/Assert lève une InvalidArgumentException de la SPL
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Aucun numéro SIRET/SIREN renseigné');

        ($this->useCase)('mon-cabinet', 'admin@kysure.com');
    }

    public function testHandlesInactiveOrClosedCompanyCorrectly(): void
    {
        // 1. Préparation des données (Given)
        $workspace = $this->createWorkspaceEntity('Cabinet Kysure', '12345678901234');
        $failedSirenResult = new SirenResult(false, 'Entreprise radiée', 'C');

        $this->workspaceRepository->method('findOneBySlug')->willReturn($workspace);

        $this->siretChecker
            ->expects($this->once())
            ->method('verifyStatus')
            ->with('12345678901234', 'Cabinet Kysure')
            ->willReturn($failedSirenResult);

        // 2. Vérification des appels sortants (Expectations)
        $this->workspaceRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($workspace));

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(WorkspaceSiretCheckFailedEvent::class));

        $this->logger
            ->expects($this->once())
            ->method('warning');

        // 3. Exécution (When)
        ($this->useCase)('mon-cabinet', 'admin@kysure.com');

        // 4. Vérification des mutations d'état internes (Then)
        $this->assertFalse($workspace->isSiretValid);
        $this->assertSame('C', $workspace->etatAdministratif);
    }

    public function testHandlesActiveCompanyCorrectly(): void
    {
        // 1. Préparation des données (Given)
        $workspace = $this->createWorkspaceEntity('Cabinet Kysure', '12345678901234');
        $successSirenResult = new SirenResult(true, 'Entreprise active', 'A');

        $this->workspaceRepository->method('findOneBySlug')->willReturn($workspace);

        $this->siretChecker
            ->expects($this->once())
            ->method('verifyStatus')
            ->willReturn($successSirenResult);

        // 2. Vérification des appels sortants (Expectations)
        $this->workspaceRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($workspace));

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(WorkspaceSiretVerifiedEvent::class));

        $this->logger
            ->expects($this->once())
            ->method('info');

        // 3. Exécution (When)
        ($this->useCase)('mon-cabinet', 'admin@kysure.com');

        // 4. Vérification des mutations d'état internes (Then)
        $this->assertTrue($workspace->isSiretValid);
        $this->assertSame('A', $workspace->etatAdministratif);
    }

    /**
     * Helper pour créer une entité Workspace pure sans se soucier de son constructeur
     * et en contournant le verrou `public private(set)` de PHP 8.4 pour les tests.
     */
    private function createWorkspaceEntity(string $name, string $siret): Workspace
    {
        $reflection = new \ReflectionClass(Workspace::class);
        $workspace = $reflection->newInstanceWithoutConstructor();

        $reflection->getProperty('name')->setValue($workspace, $name);
        $reflection->getProperty('siret')->setValue($workspace, $siret);

        // Initialisation de valeurs par défaut pour éviter les erreurs de typage non initialisé
        $reflection->getProperty('isSiretValid')->setValue($workspace, false);
        $reflection->getProperty('etatAdministratif')->setValue($workspace, 'A');

        return $workspace;
    }
}
