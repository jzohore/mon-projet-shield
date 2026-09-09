<?php

declare(strict_types=1);

namespace App\Tests\Domain\Workspace\Entity;

use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Enum\InvitationStatus;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\TestCase;

final class WorkspaceInvitationAnonymizeTest extends TestCase
{
    use ReflectionHelperTrait;

    public function testAnonymizeAsExpiredWipesRecipientPiiAndFreezesTheRow(): void
    {
        $invitation = $this->createEntityState(WorkspaceInvitation::class, [
            'email' => 'collab@cabinet.fr',
            'firstName' => 'Marie',
            'lastName' => 'Curie',
            'invitationStatus' => InvitationStatus::PENDING,
            'magicLinkToken' => WorkspaceInvitation::hashToken('plain'),
            'magicLinkTokenExpiresAt' => new \DateTimeImmutable('-100 days'),
        ]);

        $invitation->anonymizeAsExpired();

        self::assertSame('', $invitation->email);
        self::assertSame('', $invitation->firstName);
        self::assertSame('', $invitation->lastName);
        self::assertSame(InvitationStatus::EXPIRED, $invitation->invitationStatus);
        self::assertNull($invitation->magicLinkToken);
    }
}
