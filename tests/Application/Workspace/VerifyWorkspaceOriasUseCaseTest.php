<?php

declare(strict_types=1);

namespace App\Tests\Application\Workspace;

use App\Application\Workspace\UseCase\VerifyOrias\VerifyWorkspaceOriasUseCase;
use App\Domain\Firm\Entity\RegulatoryProfile;
use App\Domain\Firm\Repository\RegulatoryProfileRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Event\WorkspaceOriasCheckFailedEvent;
use App\Domain\Workspace\Event\WorkspaceOriasCheckSucceededEvent;
use App\Domain\Workspace\Exception\OriasRegistryUnavailableException;
use App\Domain\Workspace\Exception\WorkspaceNotFoundException;
use App\Domain\Workspace\Gateway\OriasCheckerInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Domain\Workspace\ValueObject\OriasStatusResult;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

final class VerifyWorkspaceOriasUseCaseTest extends TestCase
{
    private WorkspaceRepositoryInterface&MockObject $workspaceRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private LoggerInterface&MockObject $logger;
    private RegulatoryProfileRepositoryInterface&MockObject $regulatoryProfileRepository;
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
            $this->regulatoryProfileRepository,
        );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenWorkspaceNotFound(): void
    {
        $this->workspaceRepository->method('findOneBySlug')->willReturn(null);

        $this->expectException(WorkspaceNotFoundException::class);

        ($this->useCase)('invalid-slug', 'admin@kysure.fr');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenRegulatoryProfileIsMissing(): void
    {
        $this->workspaceRepository->method('findOneBySlug')
            ->willReturn($this->workspace('cabinet-test', siren: '070012345', withProfile: false));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ne possède pas de profil réglementaire');

        ($this->useCase)('cabinet-test', 'admin@kysure.fr');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsWhenNoSirenNorSiretIsExploitable(): void
    {
        $this->workspaceRepository->method('findOneBySlug')
            ->willReturn($this->workspace('cabinet-test', siren: null, withProfile: true));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Aucun numéro SIREN/SIRET exploitable pour le cabinet "cabinet-test".');

        ($this->useCase)('cabinet-test', 'admin@kysure.fr');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testDerivesSirenFromSiretWhenSirenIsMissing(): void
    {
        $workspace = $this->workspace('cabinet-test', siren: null, withProfile: true, siret: '123 456 789 00012');
        $this->workspaceRepository->method('findOneBySlug')->willReturn($workspace);
        $this->oriasCheckerFake->result = OriasStatusResult::valid('123456789', 'Inscrit', 'CABINET TEST');

        ($this->useCase)('cabinet-test', 'admin@kysure.fr');

        self::assertSame('123456789', $this->oriasCheckerFake->calledWith);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testMarksProfileInvalidAndDispatchesFailedEventWhenNotRegistered(): void
    {
        $workspace = $this->workspace('cabinet-test', siren: '070012345', withProfile: true);
        $profile = $workspace->regulatoryProfile;
        self::assertNotNull($profile);

        $this->workspaceRepository->method('findOneBySlug')->willReturn($workspace);
        $this->oriasCheckerFake->result = OriasStatusResult::notRegistered('070012345', 'Introuvable sur le registre.');

        $this->regulatoryProfileRepository->expects(self::once())->method('save')->with(self::identicalTo($profile));
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static function (object $event): object {
                self::assertInstanceOf(WorkspaceOriasCheckFailedEvent::class, $event);
                self::assertSame('070012345', $event->oriasNumber);

                return $event;
            });

        try {
            ($this->useCase)('cabinet-test', 'admin@kysure.fr');
            self::fail('Exception attendue.');
        } catch (\RuntimeException $e) {
            self::assertSame('Échec de la vérification ORIAS : Introuvable sur le registre.', $e->getMessage());
        }

        self::assertSame('070012345', $this->oriasCheckerFake->calledWith);
        self::assertFalse($profile->isValidOrias);
        self::assertNotNull($profile->lastCheckOrias);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testLeavesProfileUntouchedAndThrowsRetryableWhenRegistryUnavailable(): void
    {
        $workspace = $this->workspace('cabinet-test', siren: '070012345', withProfile: true);
        $profile = $workspace->regulatoryProfile;
        self::assertNotNull($profile);
        self::assertTrue($profile->isValidOrias, 'Précondition : profil réputé conforme.');

        $this->workspaceRepository->method('findOneBySlug')->willReturn($workspace);
        $this->oriasCheckerFake->result = OriasStatusResult::unavailable('070012345', 'Registre injoignable.');

        $this->regulatoryProfileRepository->expects(self::never())->method('save');
        $this->eventDispatcher->expects(self::never())->method('dispatch');

        $this->expectException(OriasRegistryUnavailableException::class);

        try {
            ($this->useCase)('cabinet-test', 'admin@kysure.fr');
        } finally {
            self::assertTrue($profile->isValidOrias, 'Le statut de conformité ne doit pas changer.');
            self::assertNull($profile->lastCheckOrias);
        }
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testUpdatesProfileAndDispatchesSuccessEventWhenValid(): void
    {
        $workspace = $this->workspace('cabinet-test', siren: '070012345', withProfile: true);
        $profile = $workspace->regulatoryProfile;
        self::assertNotNull($profile);

        $this->workspaceRepository->method('findOneBySlug')->willReturn($workspace);
        $validResult = OriasStatusResult::valid('070012345', 'Inscrit', 'CABINET TEST', ['COA'], ['CNCEF']);
        $this->oriasCheckerFake->result = $validResult;

        $this->regulatoryProfileRepository->expects(self::once())->method('save')->with(self::identicalTo($profile));
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static function (object $event) use ($validResult): object {
                self::assertInstanceOf(WorkspaceOriasCheckSucceededEvent::class, $event);
                self::assertSame($validResult, $event->oriasResult);

                return $event;
            });

        ($this->useCase)('cabinet-test', 'admin@kysure.fr');

        self::assertSame('070012345', $this->oriasCheckerFake->calledWith);
        self::assertTrue($profile->isValidOrias);
        self::assertNotNull($profile->lastCheckOrias);
    }

    private function workspace(string $name, ?string $siren, bool $withProfile, ?string $siret = null): Workspace
    {
        $ref = new \ReflectionClass(Workspace::class);
        $workspace = $ref->newInstanceWithoutConstructor();
        $ref->getProperty('name')->setValue($workspace, $name);
        $ref->getProperty('siren')->setValue($workspace, $siren);
        $ref->getProperty('siret')->setValue($workspace, $siret);

        if ($withProfile) {
            $ref->getProperty('regulatoryProfile')->setValue($workspace, new RegulatoryProfile($workspace));
        }

        return $workspace;
    }
}

/**
 * Fake d'infrastructure : insensible aux règles de finalité, compris par PHPStan.
 */
final class FakeOriasChecker implements OriasCheckerInterface
{
    public ?OriasStatusResult $result = null;
    public ?string $calledWith = null;

    public function checkNumber(string $oriasNumber): OriasStatusResult
    {
        $this->calledWith = $oriasNumber;

        return $this->result ?? throw new \RuntimeException('FakeOriasChecker non configuré.');
    }
}
