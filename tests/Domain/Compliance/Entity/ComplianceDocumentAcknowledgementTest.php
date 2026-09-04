<?php

declare(strict_types=1);

namespace App\Tests\Domain\Compliance\Entity;

use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Entity\DerAcknowledgement;
use App\Domain\Compliance\ValueObject\DerStatement;
use App\Domain\User\Entity\User;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class ComplianceDocumentAcknowledgementTest extends TestCase
{
    use ReflectionHelperTrait;

    /**
     * @param list<DerAcknowledgement> $acknowledgements
     * @param array<string, mixed>     $overrides
     */
    private function document(array $acknowledgements = [], array $overrides = []): ComplianceDocument
    {
        $folder = $this->createEntityState(BusinessFolder::class, ['slugId' => 'comp_fol_1', 'history' => []]);

        return $this->createEntityState(ComplianceDocument::class, array_merge([
            'slugId' => 'comp_doc_1',
            'folder' => $folder,
            'acknowledgements' => new ArrayCollection($acknowledgements),
        ], $overrides));
    }

    private function acknowledgement(ComplianceDocument $document): DerAcknowledgement
    {
        return DerAcknowledgement::record(
            document: $document,
            pdfSha256: str_repeat('a', 64),
            pdfStoragePath: 'documents/der/comp_fol_1/der.pdf',
            declaredName: 'Alice Martin',
            recipientEmail: 'client@acme.test',
            statement: DerStatement::current(),
        );
    }

    public function testIssueTokenReturnsAClearValueAndStoresOnlyItsHash(): void
    {
        $document = $this->document();

        $clearToken = $document->issueAcknowledgementToken();

        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $clearToken);
        self::assertSame(hash('sha256', $clearToken), $document->acknowledgementTokenHash);
        self::assertNotSame($clearToken, $document->acknowledgementTokenHash);
        self::assertNotNull($document->acknowledgementTokenExpiresAt);
        self::assertFalse($document->isAcknowledgementTokenExpired());
    }

    public function testTokenIsExpiredWhenNeverIssuedOrPastDeadline(): void
    {
        self::assertTrue($this->document()->isAcknowledgementTokenExpired());

        $document = $this->createEntityState(ComplianceDocument::class, [
            'acknowledgements' => new ArrayCollection(),
            'acknowledgementTokenExpiresAt' => new \DateTimeImmutable('-1 day'),
        ]);

        self::assertTrue($document->isAcknowledgementTokenExpired());
    }

    public function testHasAcknowledgementInForceReflectsTheCollection(): void
    {
        self::assertFalse($this->document()->hasAcknowledgementInForce());

        $withInForce = $this->document();
        $withInForce->acknowledgements->add($this->acknowledgement($withInForce));
        self::assertTrue($withInForce->hasAcknowledgementInForce());

        $withRevoked = $this->document();
        $revoked = $this->acknowledgement($withRevoked);
        $revoked->revoke($this->createEntityState(User::class, ['firstName' => 'Marie', 'lastName' => 'Curie']), 'motif');
        $withRevoked->acknowledgements->add($revoked);
        self::assertFalse($withRevoked->hasAcknowledgementInForce());
    }

    public function testRegenerationIsBlockedOnceTheDerIsAcknowledged(): void
    {
        $document = $this->document();
        $document->acknowledgements->add($this->acknowledgement($document));

        $this->expectException(\DomainException::class);
        $document->markAsPending('cgp@cabinet.fr');
    }

    public function testRegenerationIsAllowedWhileNoAcknowledgementIsInForce(): void
    {
        $document = $this->document();

        $document->markAsPending('cgp@cabinet.fr');

        self::assertNotEmpty($document->folder->history);
    }

    public function testIssueTokenIsBlockedOnceTheDerIsAcknowledged(): void
    {
        $document = $this->document();
        $document->acknowledgements->add($this->acknowledgement($document));

        $this->expectException(\DomainException::class);
        $document->issueAcknowledgementToken();
    }

    public function testReopenAcknowledgementCircuitResetsSendAndDeclineState(): void
    {
        $document = $this->document([], [
            'derSendRequestedAt' => new \DateTimeImmutable('-2 days'),
            'derLinkSentAt' => new \DateTimeImmutable('-1 day'),
            'derDeclinedAt' => new \DateTimeImmutable('-1 hour'),
            'derDeclineReason' => 'Ce n\'est pas mon cabinet',
        ]);

        $document->reopenAcknowledgementCircuit();

        self::assertNull($document->derSendRequestedAt);
        self::assertNull($document->derLinkSentAt);
        self::assertNull($document->derDeclinedAt);
        self::assertNull($document->derDeclineReason);
    }

    public function testLastRevokedAcknowledgementReturnsTheMostRecentRevokedOne(): void
    {
        $document = $this->document();
        $inForce = $this->acknowledgement($document);
        // ⚠️ On hydrate directement acknowledgedAt/revokedAt (plutôt que
        // record()+revoke()) pour maîtriser précisément l'ordonnancement,
        // sans dépendre de la résolution temporelle entre deux appels à now().
        $olderRevoked = $this->createEntityState(DerAcknowledgement::class, [
            'acknowledgedAt' => new \DateTimeImmutable('-2 days'),
            'revokedAt' => new \DateTimeImmutable('-1 day'),
        ]);
        $mostRecentRevoked = $this->createEntityState(DerAcknowledgement::class, [
            'acknowledgedAt' => new \DateTimeImmutable('-1 hour'),
            'revokedAt' => new \DateTimeImmutable('-30 minutes'),
        ]);

        $document->acknowledgements->add($inForce);
        $document->acknowledgements->add($olderRevoked);
        $document->acknowledgements->add($mostRecentRevoked);

        self::assertSame($mostRecentRevoked, $document->lastRevokedAcknowledgement());
    }

    public function testLastRevokedAcknowledgementReturnsNullWhenNoneRevoked(): void
    {
        $document = $this->document();
        $document->acknowledgements->add($this->acknowledgement($document));

        self::assertNull($document->lastRevokedAcknowledgement());
    }
}
