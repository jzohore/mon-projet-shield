<?php

declare(strict_types=1);

namespace App\Tests\Domain\Workspace\Entity;

use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

final class WorkspaceInvitationResendCooldownTest extends TestCase
{
    use ClockSensitiveTrait;
    use ReflectionHelperTrait;

    public function testNoCooldownWhenNeverResent(): void
    {
        $invitation = $this->createEntityState(WorkspaceInvitation::class, []);

        self::assertSame(0, $invitation->secondsUntilResendAllowed());
    }

    public function testCooldownCountsDownFromLastResent(): void
    {
        self::mockTime(new \DateTimeImmutable('2026-09-09 10:00:00'));

        $invitation = $this->createEntityState(WorkspaceInvitation::class, []);
        $invitation->markResent();

        self::assertSame(60, $invitation->secondsUntilResendAllowed());

        self::mockTime('+45 seconds');
        self::assertSame(15, $invitation->secondsUntilResendAllowed());

        self::mockTime('+30 seconds');
        self::assertSame(0, $invitation->secondsUntilResendAllowed());
    }
}
