<?php

declare(strict_types=1);

namespace App\Tests\Domain\User\Entity;

use App\Domain\Compliance\Enum\RelationshipEndReason;
use App\Domain\User\Entity\Client;
use App\Domain\Workspace\Entity\Workspace;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

/**
 * L'axe en attente / active / clôturée de la relation d'affaires est propre à
 * chaque cabinet ; le compte {@see Client} reste mutualisé.
 */
final class ClientWorkspaceRelationTest extends TestCase
{
    use ReflectionHelperTrait;

    private function workspace(string $slug): Workspace
    {
        return $this->createEntityState(Workspace::class, [
            'slugId' => $slug,
            'name' => 'Cabinet ' . $slug,
            'clients' => new ArrayCollection(),
        ]);
    }

    private function client(): Client
    {
        return $this->createEntityState(Client::class, [
            'slugId' => 'cli_1',
            'email' => 'jean@example.com',
            'firstName' => 'Jean',
            'lastName' => 'Dupont',
            'isActif' => false,
            'workspaces' => new ArrayCollection(),
            'relations' => new ArrayCollection(),
            'complianceFolders' => new ArrayCollection(),
        ]);
    }

    public function testAttachOpensAPendingRelationNotAnActiveOne(): void
    {
        $client = $this->client();
        $cabinetA = $this->workspace('wrk_a');

        $client->attachToWorkspace($cabinetA, 'Jean', 'Dupont');

        self::assertTrue($client->isPendingFor($cabinetA));
        self::assertFalse($client->isActiveFor($cabinetA));
        self::assertFalse($client->isActif, 'une relation en attente ne rend pas le compte actif');
        self::assertSame('Jean DUPONT', $client->relationWith($cabinetA)->invitedFullName());
    }

    public function testConfirmationActivatesTheRelationForThatCabinetOnly(): void
    {
        $client = $this->client();
        $cabinetA = $this->workspace('wrk_a');
        $cabinetB = $this->workspace('wrk_b');
        $client->attachToWorkspace($cabinetA);
        $client->attachToWorkspace($cabinetB);

        $client->confirmRelationWith($cabinetA);

        self::assertTrue($client->isActiveFor($cabinetA));
        self::assertTrue($client->isPendingFor($cabinetB));
        self::assertTrue($client->isActif);
    }

    public function testEndingWithOneCabinetLeavesTheOtherRelationUntouched(): void
    {
        $client = $this->client();
        $cabinetA = $this->workspace('wrk_a');
        $cabinetB = $this->workspace('wrk_b');
        $client->attachToWorkspace($cabinetA);
        $client->confirmRelationWith($cabinetA);
        $client->attachToWorkspace($cabinetB);
        $client->confirmRelationWith($cabinetB);

        $client->endRelationWith($cabinetA, RelationshipEndReason::FIN_DE_MANDAT);

        self::assertFalse($client->isActiveFor($cabinetA));
        self::assertSame(RelationshipEndReason::FIN_DE_MANDAT, $client->relationWith($cabinetA)->endReason);
        self::assertTrue($client->isActiveFor($cabinetB));
        self::assertTrue($client->isActif, 'encore actif chez le cabinet B');
    }

    public function testGlobalFlagDropsOnlyWhenNoCabinetRelationIsActive(): void
    {
        $client = $this->client();
        $cabinetA = $this->workspace('wrk_a');
        $client->attachToWorkspace($cabinetA);
        $client->confirmRelationWith($cabinetA);

        $client->endRelationWith($cabinetA, RelationshipEndReason::DEPART_CLIENT);

        self::assertFalse($client->isActif);
        self::assertFalse($client->hasAnyActiveRelation());
    }

    public function testReattachingAfterAClosureReopensTheSameRelationAsPending(): void
    {
        $client = $this->client();
        $cabinetA = $this->workspace('wrk_a');
        $client->attachToWorkspace($cabinetA);
        $client->confirmRelationWith($cabinetA);
        $client->endRelationWith($cabinetA, RelationshipEndReason::NON_REPONSE_PROLONGEE);

        $client->attachToWorkspace($cabinetA, 'Jean', 'Dupont');

        self::assertTrue($client->isPendingFor($cabinetA));
        self::assertNull($client->relationWith($cabinetA)->endReason);
        self::assertNull($client->relationWith($cabinetA)->confirmedAt);
        self::assertCount(1, $client->relations, 'pas de doublon de relation');
    }

    public function testDetachRemovesTheRelationRowForThatCabinet(): void
    {
        $client = $this->client();
        $cabinetA = $this->workspace('wrk_a');
        $cabinetB = $this->workspace('wrk_b');
        $client->attachToWorkspace($cabinetA);
        $client->attachToWorkspace($cabinetB);

        $client->detachFromWorkspace($cabinetA);

        self::assertNull($client->relationWith($cabinetA));
        self::assertNotNull($client->relationWith($cabinetB));
        self::assertFalse($client->workspaces->contains($cabinetA));
    }

    public function testConfirmationIsIdempotentAndNeverRevivesAnEndedRelation(): void
    {
        $client = $this->client();
        $cabinetA = $this->workspace('wrk_a');
        $client->attachToWorkspace($cabinetA);
        $client->confirmRelationWith($cabinetA);
        $firstConfirmedAt = $client->relationWith($cabinetA)->confirmedAt;

        $client->confirmRelationWith($cabinetA);
        self::assertSame($firstConfirmedAt, $client->relationWith($cabinetA)->confirmedAt);

        $client->endRelationWith($cabinetA, RelationshipEndReason::DEMANDE_CLIENT);
        $client->confirmRelationWith($cabinetA);
        self::assertFalse($client->isActiveFor($cabinetA), 'confirmer ne ressuscite pas une relation clôturée');
    }
}
