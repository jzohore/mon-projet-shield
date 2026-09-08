<?php

declare(strict_types=1);

namespace App\Tests\Domain\Workspace\Entity;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Enum\InvitationStatus;
use App\Domain\Workspace\Enum\InvitedRole;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\TestCase;

final class WorkspaceInvitationTokenTest extends TestCase
{
    use ReflectionHelperTrait;

    private function invitation(): WorkspaceInvitation
    {
        return WorkspaceInvitation::create(
            owner: $this->createEntityState(User::class, []),
            workspace: $this->createEntityState(Workspace::class, ['name' => 'Cabinet']),
            email: 'collab@cabinet.fr',
            firstName: 'Marie',
            lastName: 'Curie',
            invitedRole: InvitedRole::ROLE_WORKSPACE_COLLAB,
        );
    }

    public function testOnlyTheHashIsStoredAndThePlainTokenIsAvailableInMemory(): void
    {
        $invitation = $this->invitation();

        self::assertNotNull($invitation->plainMagicLinkToken);
        self::assertNotSame($invitation->plainMagicLinkToken, $invitation->magicLinkToken);
        self::assertSame(WorkspaceInvitation::hashToken($invitation->plainMagicLinkToken), $invitation->magicLinkToken);
        self::assertSame(64, \strlen($invitation->magicLinkToken));
    }

    public function testTokenIsInvalidOnceTheInvitationIsNoLongerPending(): void
    {
        $invitation = $this->invitation();
        self::assertTrue($invitation->isMagicLinkTokenValid());

        $invitation->accept();

        self::assertFalse($invitation->isMagicLinkTokenValid());
    }

    public function testRegeneratingRotatesBothTheHashAndThePlainToken(): void
    {
        $invitation = $this->invitation();
        $firstHash = $invitation->magicLinkToken;
        $firstPlain = $invitation->plainMagicLinkToken;

        $invitation->generateMagicLinkToken();

        self::assertNotSame($firstHash, $invitation->magicLinkToken);
        self::assertNotSame($firstPlain, $invitation->plainMagicLinkToken);
    }

    public function testClearWipesEverything(): void
    {
        $invitation = $this->invitation();
        $invitation->clearMagicLinkToken();

        self::assertNull($invitation->magicLinkToken);
        self::assertNull($invitation->plainMagicLinkToken);
        self::assertNull($invitation->magicLinkTokenExpiresAt);
    }

    public function testStatusHelper(): void
    {
        $invitation = $this->invitation();
        self::assertSame(InvitationStatus::PENDING, $invitation->invitationStatus);
        self::assertTrue($invitation->isPending());
    }
}
