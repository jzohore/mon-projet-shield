<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Compliance\Twig\Components\ComplianceDocument;

use App\Application\Compliance\UseCase\ComplianceDocument\AddDocumentUseCase;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\GenerateDerUseCase;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\RevokeDerAcknowledgementUseCase;
use App\Application\Compliance\UseCase\ComplianceFolder\ComplianceFolderShowAssembler;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Entity\DerAcknowledgement;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Compliance\ValueObject\DerStatement;
use App\Infrastructure\Compliance\Twig\Components\ComplianceDocument\GenerateDERComponent;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Bundle\SecurityBundle\Security;

final class GenerateDERComponentTest extends TestCase
{
    use ReflectionHelperTrait;

    private ComplianceDocumentRepositoryInterface&Stub $documentRepository;
    private RevokeDerAcknowledgementUseCase&MockObject $revokeDerAcknowledgementUseCase;
    private Security&Stub $security;
    private GenerateDERComponent $component;

    protected function setUp(): void
    {
        $this->documentRepository = $this->createStub(ComplianceDocumentRepositoryInterface::class);
        $this->revokeDerAcknowledgementUseCase = $this->createMock(RevokeDerAcknowledgementUseCase::class);
        $this->security = $this->createStub(Security::class);

        $this->component = new GenerateDERComponent(
            $this->createStub(AddDocumentUseCase::class),
            $this->createStub(GenerateDerUseCase::class),
            $this->documentRepository,
            new NullLogger(),
            $this->createStub(ComplianceFolderShowAssembler::class),
            $this->revokeDerAcknowledgementUseCase,
            $this->security,
        );
    }

    /**
     * Dossier avec un DER acquitté (accusé en vigueur), condition nécessaire
     * pour que revokeAcknowledgement() dépasse ses gardes préliminaires.
     */
    private function folderWithAcknowledgedDer(): BusinessFolder
    {
        $folder = $this->createEntityState(BusinessFolder::class, ['slugId' => 'comp_fol_1', 'history' => []]);
        $document = $this->createEntityState(ComplianceDocument::class, [
            'folder' => $folder,
            'acknowledgements' => new ArrayCollection(),
        ]);
        $document->acknowledgements->add(DerAcknowledgement::record(
            document: $document,
            pdfSha256: str_repeat('a', 64),
            pdfStoragePath: 'p.pdf',
            declaredName: 'Alice',
            recipientEmail: 'a@b.test',
            statement: DerStatement::current(),
        ));

        $this->documentRepository->method('findDerByFolder')->willReturn($document);

        return $folder;
    }

    public function testRevokeAcknowledgementDeniesAnUnauthorizedUser(): void
    {
        $this->component->complianceFolder = $this->folderWithAcknowledgedDer();
        $this->component->revokeReason = 'motif valide';
        $this->security->method('isGranted')->willReturn(false);

        $this->revokeDerAcknowledgementUseCase->expects($this->never())->method('__invoke');

        $this->component->revokeAcknowledgement();

        self::assertSame('error', $this->component->actionMessageType);
    }

    public function testRevokeAcknowledgementRequiresANonBlankReason(): void
    {
        $this->component->complianceFolder = $this->folderWithAcknowledgedDer();
        $this->component->revokeReason = '   ';
        $this->security->method('isGranted')->willReturn(true);

        $this->revokeDerAcknowledgementUseCase->expects($this->never())->method('__invoke');

        $this->component->revokeAcknowledgement();

        self::assertSame('error', $this->component->actionMessageType);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testRevokeAcknowledgementCallsTheUseCaseOnSuccess(): void
    {
        $this->component->complianceFolder = $this->folderWithAcknowledgedDer();
        $this->component->revokeReason = 'Destinataire erroné';
        $this->security->method('isGranted')->willReturn(true);

        $this->revokeDerAcknowledgementUseCase->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static fn (string $slugId): bool => '' !== $slugId), 'Destinataire erroné');

        $this->component->revokeAcknowledgement();

        self::assertSame('success', $this->component->actionMessageType);
        self::assertSame('', $this->component->revokeReason);
    }
}
