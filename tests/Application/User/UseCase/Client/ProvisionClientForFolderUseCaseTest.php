<?php

declare(strict_types=1);

namespace App\Tests\Application\User\UseCase\Client;

use App\Application\Compliance\DTO\Response\ComplianceFolderShowResponse;
use App\Application\Compliance\UseCase\ComplianceFolder\ComplianceFolderShowAssembler;
use App\Application\User\UseCase\Client\AttachExistingClientUseCase;
use App\Application\User\UseCase\Client\CreateClientUseCase;
use App\Application\User\UseCase\Client\ProvisionClientForFolderUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\User\Entity\Client;
use App\Domain\User\Exception\ClientNotFoundException;
use App\Domain\Workspace\Entity\Workspace;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

final class ProvisionClientForFolderUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private ComplianceFolderShowAssembler&Stub $assembler;
    private AttachExistingClientUseCase&MockObject $attachExisting;
    private CreateClientUseCase&MockObject $createClient;
    private ProvisionClientForFolderUseCase $useCase;

    protected function setUp(): void
    {
        $this->assembler = $this->createStub(ComplianceFolderShowAssembler::class);
        $this->attachExisting = $this->createMock(AttachExistingClientUseCase::class);
        $this->createClient = $this->createMock(CreateClientUseCase::class);
        $this->useCase = new ProvisionClientForFolderUseCase($this->assembler, $this->attachExisting, $this->createClient);
    }

    private function folder(?Client $client = null): ComplianceFolder
    {
        $workspace = $this->createEntityState(Workspace::class, ['slugId' => 'wrk_1', 'name' => 'Cabinet Durand']);

        return $this->createEntityState(BusinessFolder::class, [
            'slugId' => 'comp_fol_1',
            'workspace' => $workspace,
            'client' => $client,
        ]);
    }

    private function client(): Client
    {
        return $this->createEntityState(Client::class, ['email' => 'client@acme.test']);
    }

    private function assemblerReturns(): void
    {
        $this->assembler->method('assemble')->willReturn(new ComplianceFolderShowResponse(
            id: 'id', slugId: 'comp_fol_1', workspaceName: 'Cabinet Durand', workspaceEmail: 'c@d.fr',
            reference: 'DOS-1', statusValue: 's', statusLabel: 'S', isManual: false, isKyb: true,
            isDraft: false, isArchived: false, isAcceptedRecording: false, method: 'flash',
            headerTitle: 'ACME', headerSubtitle: 'SIRET', contactName: 'Alice Martin', workspaceRemainingMinutes: 0,
            companyDocuments: [], individualDocuments: [], stakeholders: [], history: [],
            contactFirstName: 'Alice', contactLastName: 'Martin', contactEmail: 'client@acme.test', type: 'business',
        ));
    }

    public function testAttachesAnExistingClientAndBindsItToTheFolder(): void
    {
        $this->assemblerReturns();
        $folder = $this->folder();
        $client = $this->client();

        $this->attachExisting->expects($this->once())->method('__invoke')
            ->with('client@acme.test', 'wrk_1')->willReturn($client);
        $this->createClient->expects($this->never())->method('__invoke');

        self::assertSame($client, ($this->useCase)($folder));
        self::assertSame($client, $folder->client);
    }

    public function testCreatesTheClientWhenNoneExistsYet(): void
    {
        $this->assemblerReturns();
        $folder = $this->folder();
        $client = $this->client();

        $this->attachExisting->expects($this->once())->method('__invoke')
            ->willThrowException(ClientNotFoundException::withEmail('client@acme.test'));
        $this->createClient->expects($this->once())->method('__invoke')
            ->with('client@acme.test', 'Alice', 'Martin', 'wrk_1')->willReturn($client);

        self::assertSame($client, ($this->useCase)($folder));
        self::assertSame($client, $folder->client);
    }

    public function testIsIdempotentWhenTheFolderAlreadyHasAClient(): void
    {
        $existing = $this->client();
        $folder = $this->folder($existing);

        $this->attachExisting->expects($this->never())->method('__invoke');
        $this->createClient->expects($this->never())->method('__invoke');

        self::assertSame($existing, ($this->useCase)($folder));
    }
}
