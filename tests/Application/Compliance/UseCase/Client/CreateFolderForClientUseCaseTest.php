<?php

declare(strict_types=1);

namespace App\Tests\Application\Compliance\UseCase\Client;

use App\Application\Compliance\UseCase\Client\CreateFolderForClientUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\IndividualFolder;
use App\Domain\Compliance\Exception\InvalidFolderTypeException;
use App\Domain\Compliance\Factory\ComplianceFolderFactory;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Compliance\Service\DocumentRequirementEngine;
use App\Domain\User\Entity\Client;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\ClientRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

final class CreateFolderForClientUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private ClientRepositoryInterface&Stub $clientRepository;
    private ComplianceFolderRepositoryInterface&MockObject $folderRepository;
    private DocumentRequirementEngine&MockObject $requirementEngine;
    private Workspace $workspace;
    private CreateFolderForClientUseCase $useCase;

    protected function setUp(): void
    {
        $this->clientRepository = $this->createStub(ClientRepositoryInterface::class);
        $this->folderRepository = $this->createMock(ComplianceFolderRepositoryInterface::class);
        $this->requirementEngine = $this->createMock(DocumentRequirementEngine::class);

        $this->workspace = $this->createEntityState(Workspace::class, ['slugId' => 'wrk_1', 'name' => 'Cabinet']);

        $workspaceProvider = $this->createStub(CurrentWorkspaceProvider::class);
        $workspaceProvider->method('getWorkspace')->willReturn($this->workspace);

        $userProvider = $this->createStub(CurrentUserProvider::class);
        $userProvider->method('getUser')->willReturn(
            $this->createEntityState(User::class, ['slugId' => 'usr_1', 'firstName' => 'Marie', 'lastName' => 'Curie'])
        );

        $this->useCase = new CreateFolderForClientUseCase(
            $this->clientRepository,
            $this->folderRepository,
            new ComplianceFolderFactory(),
            $this->requirementEngine,
            $workspaceProvider,
            $userProvider,
        );
    }

    private function client(): Client
    {
        return $this->createEntityState(Client::class, [
            'slugId' => 'cli_1',
            'email' => 'jean@example.com',
            'firstName' => 'Jean',
            'lastName' => 'Dupont',
            'workspaces' => new ArrayCollection([$this->workspace]),
            'complianceFolders' => new ArrayCollection(),
        ]);
    }

    public function testCreatesAnIndividualFolderPrefilledWithTheKnownClientIdentity(): void
    {
        $client = $this->client();
        $this->clientRepository->method('findOneBySlugIdAndWorkspace')->willReturn($client);
        $this->requirementEngine->expects($this->once())->method('generateBaseRequirements');

        $saved = null;
        $this->folderRepository->expects($this->once())->method('save')
            ->willReturnCallback(static function (object $folder) use (&$saved): void {
                $saved = $folder;
            });

        $slugId = ($this->useCase)('cli_1', 'individual');

        self::assertInstanceOf(IndividualFolder::class, $saved);
        self::assertSame($client, $saved->client);
        self::assertSame('Jean', $saved->firstName);
        self::assertSame('Dupont', $saved->lastName);
        self::assertSame('jean@example.com', $saved->email);
        self::assertFalse($saved->isDraftEmpty(), 'l\'étape 1 ne doit plus être bloquante');
        self::assertSame($saved->slugId, $slugId);
    }

    public function testCreatesABusinessFolderAttachedToTheClientWithoutIndividualPrefill(): void
    {
        $client = $this->client();
        $this->clientRepository->method('findOneBySlugIdAndWorkspace')->willReturn($client);
        $this->requirementEngine->expects($this->once())->method('generateBaseRequirements');

        $saved = null;
        $this->folderRepository->expects($this->once())->method('save')
            ->willReturnCallback(static function (object $folder) use (&$saved): void {
                $saved = $folder;
            });

        ($this->useCase)('cli_1', 'business');

        self::assertInstanceOf(BusinessFolder::class, $saved);
        self::assertSame($client, $saved->client);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testRejectsAnUnknownFolderType(): void
    {
        $this->expectException(InvalidFolderTypeException::class);
        ($this->useCase)('cli_1', 'spaceship');
    }
}
