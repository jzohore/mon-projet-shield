<?php

declare(strict_types=1);

namespace App\Tests\Domain\Workspace\Service;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Enum\PermissionMode;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\WorkspacePermissionChecker;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\TestCase;

final class WorkspacePermissionCheckerTest extends TestCase
{
    use ReflectionHelperTrait;

    private function checker(bool $isAdmin): WorkspacePermissionChecker
    {
        $members = $this->createStub(WorkspaceMemberRepositoryInterface::class);
        $members->method('isUserAdminOfWorkspace')->willReturn($isAdmin);

        return new WorkspacePermissionChecker($members);
    }

    private function workspace(PermissionMode $mode): Workspace
    {
        return $this->createEntityState(Workspace::class, ['name' => 'Cabinet', 'slugId' => 'wrk_1', 'validationMode' => $mode]);
    }

    public function testAdminCanValidateWhateverThePosture(): void
    {
        $user = $this->createEntityState(User::class, ['email' => 'a@b.fr']);

        self::assertTrue($this->checker(isAdmin: true)->canValidateActs($user, $this->workspace(PermissionMode::RESERVED)));
        self::assertTrue($this->checker(isAdmin: true)->canValidateActs($user, $this->workspace(PermissionMode::SUBMISSION)));
    }

    public function testCollabValidatesOnlyWhenDelegated(): void
    {
        $user = $this->createEntityState(User::class, ['email' => 'a@b.fr']);
        $checker = $this->checker(isAdmin: false);

        self::assertTrue($checker->canValidateActs($user, $this->workspace(PermissionMode::DELEGATED)));
        self::assertFalse($checker->canValidateActs($user, $this->workspace(PermissionMode::SUBMISSION)));
        self::assertFalse($checker->canValidateActs($user, $this->workspace(PermissionMode::RESERVED)));
    }
}
