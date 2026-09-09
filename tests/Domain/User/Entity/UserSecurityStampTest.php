<?php

declare(strict_types=1);

namespace App\Tests\Domain\User\Entity;

use App\Domain\User\Entity\User;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\TestCase;

final class UserSecurityStampTest extends TestCase
{
    use ReflectionHelperTrait;

    private function user(string $email, string $stamp): User
    {
        return $this->createEntityState(User::class, ['email' => $email, 'securityStamp' => $stamp]);
    }

    public function testSameIdentityAndStampAreEqual(): void
    {
        self::assertTrue($this->user('a@b.fr', 'stamp-1')->isEqualTo($this->user('a@b.fr', 'stamp-1')));
    }

    public function testDifferentStampBreaksEquality(): void
    {
        self::assertFalse($this->user('a@b.fr', 'stamp-1')->isEqualTo($this->user('a@b.fr', 'stamp-2')));
    }

    public function testDifferentIdentityBreaksEquality(): void
    {
        self::assertFalse($this->user('a@b.fr', 'stamp-1')->isEqualTo($this->user('c@d.fr', 'stamp-1')));
    }

    public function testEmptyStampIsToleratedDuringRollout(): void
    {
        self::assertTrue($this->user('a@b.fr', '')->isEqualTo($this->user('a@b.fr', 'stamp-1')));
    }

    public function testRegenerateChangesTheStamp(): void
    {
        $user = $this->user('a@b.fr', 'stamp-1');
        $user->regenerateSecurityStamp();

        self::assertNotSame('stamp-1', $user->securityStamp);
        self::assertSame(32, \strlen($user->securityStamp));
    }
}
