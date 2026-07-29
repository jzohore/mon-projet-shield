<?php

declare(strict_types=1);

namespace App\Tests\Application\Workspace;

use App\Application\Workspace\UseCase\VerifyWorkspaceOriasUseCase;
use App\Domain\Firm\Entity\RegulatoryProfile;
use App\Domain\Firm\Repository\RegulatoryProfileRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Event\WorkspaceOriasCheckFailedEvent;
use App\Domain\Workspace\Event\WorkspaceOriasCheckSucceededEvent;
use App\Domain\Workspace\Exception\WorkspaceNotFoundException;
use App\Domain\Workspace\Gateway\OriasCheckerInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Domain\Workspace\ValueObject\OriasStatusResult;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @covers \App\Application\Workspace\UseCase\VerifyWorkspaceOriasUseCase
 */
final class VerifyWorkspaceOriasUseCaseTest extends TestCase
{
    private WorkspaceRepositoryInterface&MockObject $workspaceRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private LoggerInterface&MockObject $logger;
    private RegulatoryProfileRepositoryInterface&MockObject $regulatoryProfileRepository;

    // Utilisation du Fake au lieu d'un MockObject !
    private FakeOriasChecker $oriasCheckerFake;
    private VerifyWorkspaceOriasUseCase $useCase;

    protected function setUp(): void
    {
        $this->workspaceRepository = $this->createMock(WorkspaceRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->regulatoryProfileRepository = $this->createMock(RegulatoryProfileRepositoryInterface::class);

        $this->oriasCheckerFake = new FakeOriasChecker();

        $this->useCase = new VerifyWorkspaceOriasUseCase(
            $this->workspaceRepository,
            $this->oriasCheckerFake,
            $this->eventDispatcher,
            $this->logger,
            $this->regulatoryProfileRepository
        );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenWorkspaceNotFound(): void
    {
        $this->workspaceRepository->expects(self::once())
            ->method('findOneBySlug')
            ->with('invalid-slug')
            ->willReturn(null);

        try {
            ($this->useCase)('invalid-slug', 'admin@kysure.fr');
            self::fail('Exception attendue.');
        } catch (WorkspaceNotFoundException $e) {
            self::assertInstanceOf(WorkspaceNotFoundException::class, $e);
        }
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenRegulatoryProfileIsMissing(): void
    {
        $workspace = $this->createWorkspaceEntity('cabinet-test', '1230309', false);
        $this->workspaceRepository->expects(self::once())->method('findOneBySlug')->willReturn($workspace);

        try {
            ($this->useCase)('cabinet-test', 'admin@kysure.fr');
            self::fail('Exception attendue.');
        } catch (\InvalidArgumentException $e) {
            self::assertSame('Le cabinet "cabinet-test" ne possède pas de profil réglementaire.', $e->getMessage());
        }
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenSirenIsMissing(): void
    {
        // On passe explicitement une chaîne vide `""` (ou null) pour le SIREN
        $workspace = $this->createWorkspaceEntity('cabinet-test', '', true);

        $this->workspaceRepository->expects(self::once())
            ->method('findOneBySlug')
            ->willReturn($workspace);

        try {
            // Le UseCase s'exécute, l'assertion stringNotEmpty échoue et stoppe tout.
            // Le Fake n'est JAMAIS appelé, donc pas de RuntimeException !
            ($this->useCase)('cabinet-test', 'admin@kysure.fr');
            self::fail('Une InvalidArgumentException aurait dû être levée pour l\'absence de SIREN.');
        } catch (\InvalidArgumentException $e) {
            self::assertSame('Aucun numéro SIREN renseigné pour le cabinet "cabinet-test".', $e->getMessage());
        }
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testDispatchesFailedEventAndThrowsWhenOriasIsInvalid(): void
    {
        $workspace = $this->createWorkspaceEntity('cabinet-test', '440714103', true);
        $profile = $workspace->regulatoryProfile;
        self::assertNotNull($profile);

        $this->workspaceRepository->expects(self::once())->method('findOneBySlug')->willReturn($workspace);

        // Configuration du FAKE au lieu du Mock
        $invalidResult = OriasStatusResult::invalid('440714103', 'Introuvable sur le registre.');
        $this->oriasCheckerFake->expectedResult = $invalidResult;

        $this->regulatoryProfileRepository->expects(self::once())
            ->method('save')
            ->with(self::identicalTo($profile));

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static function (object $event): object {
                self::assertInstanceOf(WorkspaceOriasCheckFailedEvent::class, $event);
                self::assertSame('440714103', $event->oriasNumber);

                return $event;
            });

        try {
            ($this->useCase)('cabinet-test', 'admin@kysure.fr');
            self::fail('Exception attendue.');
        } catch (\RuntimeException $e) {
            self::assertSame('Échec de la vérification ORIAS : Introuvable sur le registre.', $e->getMessage());
        }

        // Assertion que le Fake a bien été appelé avec le bon argument
        self::assertSame('440714103', $this->oriasCheckerFake->calledWithNumber);

        self::assertFalse($profile->isValidOrias);
        self::assertNotNull($profile->lastCheckOrias);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testUpdatesProfileAndDispatchesSuccessEventWhenOriasIsValid(): void
    {
        $workspace = $this->createWorkspaceEntity('cabinet-test', '440714103', true);
        $profile = $workspace->regulatoryProfile;
        self::assertNotNull($profile);

        $this->workspaceRepository->expects(self::once())->method('findOneBySlug')->willReturn($workspace);

        // Configuration du FAKE
        $validResult = new OriasStatusResult('440714103', true, 'Inscrit', 'API', ['COA'], ['CNCEF']);
        $this->oriasCheckerFake->expectedResult = $validResult;

        $this->regulatoryProfileRepository->expects(self::once())
            ->method('save')
            ->with(self::identicalTo($profile));

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static function (object $event) use ($validResult): object {
                self::assertInstanceOf(WorkspaceOriasCheckSucceededEvent::class, $event);
                self::assertSame($validResult, $event->oriasResult);

                return $event;
            });

        ($this->useCase)('cabinet-test', 'admin@kysure.fr');

        self::assertSame('440714103', $this->oriasCheckerFake->calledWithNumber);
        self::assertTrue($profile->isValidOrias);
        self::assertNotNull($profile->lastCheckOrias);
    }

    private function createWorkspaceEntity(string $name, ?string $siren, bool $withProfile): Workspace
    {
        $refClass = new \ReflectionClass(Workspace::class);
        $workspace = $refClass->newInstanceWithoutConstructor();

        $propName = $refClass->getProperty('name');
        $propName->setValue($workspace, $name);

        $propSiren = $refClass->getProperty('siren');
        $propSiren->setValue($workspace, $siren);

        if ($withProfile) {
            $profile = new RegulatoryProfile($workspace);
            $propProfile = $refClass->getProperty('regulatoryProfile');
            $propProfile->setValue($workspace, $profile);
        }

        return $workspace;
    }
}

/**
 * FAKE PATTERN : Remplaçant de l'infrastructure pour les tests unitaires.
 * Totalement compris par PHPStan et insensible aux règles de finalité.
 */
final class FakeOriasChecker implements OriasCheckerInterface
{
    public ?OriasStatusResult $expectedResult = null;
    public ?string $calledWithNumber = null;

    public function checkNumber(string $oriasNumber): OriasStatusResult
    {
        $this->calledWithNumber = $oriasNumber;

        if (!$this->expectedResult instanceof OriasStatusResult) {
            throw new \RuntimeException('Le FakeOriasChecker n\'a pas été configuré avec un $expectedResult.');
        }

        return $this->expectedResult;
    }
}
