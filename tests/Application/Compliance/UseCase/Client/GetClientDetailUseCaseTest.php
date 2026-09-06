<?php

declare(strict_types=1);

namespace App\Tests\Application\Compliance\UseCase\Client;

use App\Application\Compliance\UseCase\Client\GetClientDetailUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Enum\ComplianceFolderStatus;
use App\Domain\User\Entity\Client;
use App\Domain\User\Exception\ClientNotFoundException;
use App\Domain\User\Repository\ClientRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class GetClientDetailUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private ClientRepositoryInterface $clientRepository;
    private Workspace $workspace;
    private Workspace $otherWorkspace;
    private GetClientDetailUseCase $useCase;

    protected function setUp(): void
    {
        $this->clientRepository = $this->createStub(ClientRepositoryInterface::class);

        $this->workspace = $this->createEntityState(Workspace::class, ['slugId' => 'wrk_1', 'name' => 'Cabinet']);
        $this->otherWorkspace = $this->createEntityState(Workspace::class, ['slugId' => 'wrk_2', 'name' => 'Autre']);

        $workspaceProvider = $this->createStub(CurrentWorkspaceProvider::class);
        $workspaceProvider->method('getWorkspace')->willReturn($this->workspace);

        $this->useCase = new GetClientDetailUseCase($this->clientRepository, $workspaceProvider);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function folder(string $slugId, \DateTimeImmutable $createdAt, array $overrides = []): BusinessFolder
    {
        return $this->createEntityState(BusinessFolder::class, [
            'slugId' => $slugId,
            'reference' => 'DOS-' . $slugId,
            'status' => ComplianceFolderStatus::DRAFT,
            'workspace' => $this->workspace,
            'createdAt' => $createdAt,
            'isConfidential' => false,
            'isUnderLegalHold' => false,
            'submittedAt' => null,
            'relationshipEndedAt' => null,
            'purgeDueAt' => null,
            'documents' => new ArrayCollection(),
            'meetingRecordings' => new ArrayCollection(),
            ...$overrides,
        ]);
    }

    private function client(BusinessFolder ...$folders): Client
    {
        return $this->createEntityState(Client::class, [
            'slugId' => 'cli_1',
            'email' => 'jean@example.com',
            'firstName' => 'Jean',
            'lastName' => 'Dupont',
            'phoneNumber' => '+33600000000',
            'isActif' => true,
            'createdAt' => new \DateTimeImmutable('2025-01-10'),
            'workspaces' => new ArrayCollection([$this->workspace]),
            'complianceFolders' => new ArrayCollection($folders),
        ]);
    }

    public function testThrowsWhenClientNotFoundInWorkspace(): void
    {
        $this->clientRepository->method('findOneBySlugIdAndWorkspace')->willReturn(null);

        $this->expectException(ClientNotFoundException::class);
        ($this->useCase)('cli_unknown');
    }

    public function testAVirginDraftClientCanBeRemovedAndHasNoSeniority(): void
    {
        $this->clientRepository->method('findOneBySlugIdAndWorkspace')
            ->willReturn($this->client($this->folder('comp_fol_1', new \DateTimeImmutable('2025-02-01'))));

        $dto = ($this->useCase)('cli_1');

        self::assertSame('JEAN DUPONT', $dto->fullName);
        self::assertCount(1, $dto->folders);
        self::assertTrue($dto->canBeRemoved);
        self::assertNull($dto->removalBlockedReason);
        self::assertFalse($dto->canCloseRelationship);
        self::assertNull($dto->clientSinceFormatted);
        self::assertFalse($dto->folders[0]->hasDer);
    }

    public function testAnEngagedFolderBlocksRemovalAndEnablesClosureWithSeniority(): void
    {
        $this->clientRepository->method('findOneBySlugIdAndWorkspace')->willReturn(
            $this->client($this->folder('comp_fol_1', new \DateTimeImmutable('2025-03-15'), [
                'status' => ComplianceFolderStatus::APPROVED,
            ]))
        );

        $dto = ($this->useCase)('cli_1');

        self::assertFalse($dto->canBeRemoved);
        self::assertNotNull($dto->removalBlockedReason);
        self::assertTrue($dto->canCloseRelationship);
        self::assertSame('15/03/2025', $dto->clientSinceFormatted);
    }

    public function testLegalHoldBlocksRemovalWithADedicatedReason(): void
    {
        $this->clientRepository->method('findOneBySlugIdAndWorkspace')->willReturn(
            $this->client($this->folder('comp_fol_1', new \DateTimeImmutable('2025-03-15'), [
                'status' => ComplianceFolderStatus::APPROVED,
                'isUnderLegalHold' => true,
            ]))
        );

        $dto = ($this->useCase)('cli_1');

        self::assertFalse($dto->canBeRemoved);
        self::assertStringContainsString('litige', $dto->removalBlockedReason ?? '');
    }

    public function testExcludesFoldersOfOtherWorkspacesAndDeletedOnesAndSortsRecentFirst(): void
    {
        $old = $this->folder('comp_fol_old', new \DateTimeImmutable('2025-01-01'));
        $recent = $this->folder('comp_fol_recent', new \DateTimeImmutable('2025-06-01'));
        $foreign = $this->folder('comp_fol_foreign', new \DateTimeImmutable('2025-07-01'), ['workspace' => $this->otherWorkspace]);
        $deleted = $this->folder('comp_fol_del', new \DateTimeImmutable('2025-08-01'), ['status' => ComplianceFolderStatus::DELETED]);

        $this->clientRepository->method('findOneBySlugIdAndWorkspace')
            ->willReturn($this->client($old, $recent, $foreign, $deleted));

        $dto = ($this->useCase)('cli_1');

        self::assertCount(2, $dto->folders);
        self::assertSame('comp_fol_recent', $dto->folders[0]->slugId);
        self::assertSame('comp_fol_old', $dto->folders[1]->slugId);
    }
}
