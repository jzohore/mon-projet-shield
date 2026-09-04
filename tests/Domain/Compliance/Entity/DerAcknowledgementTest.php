<?php

declare(strict_types=1);

namespace App\Tests\Domain\Compliance\Entity;

use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Entity\DerAcknowledgement;
use App\Domain\Compliance\ValueObject\DerStatement;
use App\Domain\User\Entity\User;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DerAcknowledgementTest extends TestCase
{
    use ReflectionHelperTrait;

    private const string PDF = "%PDF-1.7\nDER du cabinet";

    private function document(): ComplianceDocument
    {
        return $this->createEntityState(ComplianceDocument::class, [
            'acknowledgements' => new ArrayCollection(),
        ]);
    }

    private function record(string $name = 'Alice Martin', ?string $userAgent = null): DerAcknowledgement
    {
        return DerAcknowledgement::record(
            document: $this->document(),
            pdfSha256: hash('sha256', self::PDF),
            pdfStoragePath: 'documents/der/comp_fol_1/der.pdf',
            declaredName: $name,
            recipientEmail: 'client@acme.test',
            statement: DerStatement::current(),
            ipAddress: '203.0.113.7',
            userAgent: $userAgent,
        );
    }

    public function testRecordBuildsAnInForceAcknowledgementWithFrozenProof(): void
    {
        $ack = $this->record();

        self::assertStringStartsWith('der_ack_', $ack->slugId);
        self::assertTrue($ack->isInForce());
        self::assertFalse($ack->isRevoked());
        self::assertSame(hash('sha256', self::PDF), $ack->pdfSha256);
        self::assertSame('documents/der/comp_fol_1/der.pdf', $ack->pdfStoragePath);
        self::assertSame('Alice Martin', $ack->declaredName);
        self::assertSame('client@acme.test', $ack->recipientEmail);
        self::assertSame(DerStatement::TEXT, $ack->statementText);
        self::assertSame(DerStatement::VERSION, $ack->statementVersion);
        self::assertSame('203.0.113.7', $ack->ipAddress);
    }

    public function testRecordTrimsTheDeclaredName(): void
    {
        self::assertSame('Alice Martin', $this->record('  Alice Martin  ')->declaredName);
    }

    #[DataProvider('blankNames')]
    public function testRecordRejectsABlankDeclaredName(string $name): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->record($name);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function blankNames(): iterable
    {
        yield 'empty' => [''];
        yield 'spaces' => ['   '];
    }

    public function testRecordRejectsAMalformedHash(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DerAcknowledgement::record(
            document: $this->document(),
            pdfSha256: 'not-a-sha',
            pdfStoragePath: 'x.pdf',
            declaredName: 'Alice',
            recipientEmail: 'a@b.test',
            statement: DerStatement::current(),
        );
    }

    public function testUserAgentIsTruncatedTo255Chars(): void
    {
        $ack = $this->record(userAgent: str_repeat('U', 400));

        self::assertNotNull($ack->userAgent);
        self::assertSame(255, mb_strlen($ack->userAgent));
    }

    public function testRevokeMarksItOutOfForceWithTrimmedReason(): void
    {
        $ack = $this->record();
        $cgp = $this->createEntityState(User::class, ['firstName' => 'Marie', 'lastName' => 'Curie']);

        $ack->revoke($cgp, '  Erreur de destinataire  ');

        self::assertFalse($ack->isInForce());
        self::assertTrue($ack->isRevoked());
        self::assertSame('Erreur de destinataire', $ack->revokeReason);
        self::assertSame('Marie CURIE', $ack->revokedByName);
    }

    public function testRevokeTwiceIsRejected(): void
    {
        $ack = $this->record();
        $cgp = $this->createEntityState(User::class, ['firstName' => 'Marie', 'lastName' => 'Curie']);
        $ack->revoke($cgp, 'motif');

        $this->expectException(\DomainException::class);
        $ack->revoke($cgp, 'encore');
    }

    public function testRevokeWithBlankReasonIsRejected(): void
    {
        $ack = $this->record();
        $cgp = $this->createEntityState(User::class, ['firstName' => 'Marie', 'lastName' => 'Curie']);

        $this->expectException(\DomainException::class);
        $ack->revoke($cgp, '   ');
    }

    public function testMatchesStoredHashDetectsTampering(): void
    {
        $ack = $this->record();

        self::assertTrue($ack->matchesStoredHash(self::PDF));
        self::assertFalse($ack->matchesStoredHash(self::PDF . 'altéré'));
    }
}
