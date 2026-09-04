<?php

declare(strict_types=1);

namespace App\Tests\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Application\Compliance\UseCase\ComplianceDocument\DER\ResolveDerAcknowledgementLinkUseCase;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Enum\DocumentType;
use App\Domain\Compliance\Exception\AcknowledgementLinkException;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

final class ResolveDerAcknowledgementLinkUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private const string TOKEN = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2';

    private ComplianceDocumentRepositoryInterface&Stub $documentRepository;
    private ResolveDerAcknowledgementLinkUseCase $useCase;

    protected function setUp(): void
    {
        $this->documentRepository = $this->createStub(ComplianceDocumentRepositoryInterface::class);
        $this->useCase = new ResolveDerAcknowledgementLinkUseCase($this->documentRepository);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function document(array $overrides = []): ComplianceDocument
    {
        return $this->createEntityState(ComplianceDocument::class, array_merge([
            'slugId' => 'comp_doc_1',
            'type' => DocumentType::DER,
            'acknowledgementTokenExpiresAt' => new \DateTimeImmutable('+20 days'),
            'acknowledgements' => new ArrayCollection(),
        ], $overrides));
    }

    public function testReturnsTheDerForAValidNonExpiredToken(): void
    {
        $document = $this->document();
        $this->documentRepository->method('findOneByAcknowledgementTokenHash')->willReturn($document);

        self::assertSame($document, ($this->useCase)(self::TOKEN));
    }

    public function testRejectsAMalformedToken(): void
    {
        try {
            ($this->useCase)('not-64-hex');
            self::fail('Expected AcknowledgementLinkException.');
        } catch (AcknowledgementLinkException $exception) {
            self::assertSame(AcknowledgementLinkException::REASON_INVALID, $exception->reason);
        }
    }

    public function testRejectsAnUnknownToken(): void
    {
        $this->documentRepository->method('findOneByAcknowledgementTokenHash')->willReturn(null);

        $this->expectException(AcknowledgementLinkException::class);
        ($this->useCase)(self::TOKEN);
    }

    public function testRejectsADocumentThatIsNotADer(): void
    {
        $this->documentRepository->method('findOneByAcknowledgementTokenHash')
            ->willReturn($this->document(['type' => DocumentType::ID_CARD]));

        $this->expectException(AcknowledgementLinkException::class);
        ($this->useCase)(self::TOKEN);
    }

    public function testReportsAnExpiredToken(): void
    {
        $this->documentRepository->method('findOneByAcknowledgementTokenHash')
            ->willReturn($this->document(['acknowledgementTokenExpiresAt' => new \DateTimeImmutable('-1 day')]));

        try {
            ($this->useCase)(self::TOKEN);
            self::fail('Expected AcknowledgementLinkException.');
        } catch (AcknowledgementLinkException $exception) {
            self::assertSame(AcknowledgementLinkException::REASON_EXPIRED, $exception->reason);
        }
    }
}
