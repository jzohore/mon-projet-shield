<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Workspace\Voter;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Service\WorkspacePermissionChecker;
use App\Infrastructure\Workspace\Voter\WorkspaceInvitationVoter;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class WorkspaceInvitationVoterTest extends TestCase
{
    use ReflectionHelperTrait;

    private function vote(string $attribute, bool $canInvite = false, bool $canEditCabinet = false, bool $isAdmin = false): int
    {
        $user = $this->createEntityState(User::class, ['email' => 'u@x.fr']);
        $workspace = $this->createEntityState(Workspace::class, ['name' => 'Cabinet', 'slugId' => 'wrk_1']);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $adm = $this->createStub(AccessDecisionManagerInterface::class);
        $adm->method('decide')->willReturn(false); // pas super-admin KYSURE

        $checker = $this->createStub(WorkspacePermissionChecker::class);
        $checker->method('canInvite')->willReturn($canInvite);
        $checker->method('canEditCabinet')->willReturn($canEditCabinet);
        $checker->method('isAdmin')->willReturn($isAdmin);

        return new WorkspaceInvitationVoter($adm, $checker)->vote($token, $workspace, [$attribute]);
    }

    public function testInvitationDelegatedToCollab(): void
    {
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote(WorkspaceInvitationVoter::CREATE, canInvite: true));
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote(WorkspaceInvitationVoter::CREATE, canInvite: false));
    }

    public function testCabinetEditFollowsItsOwnDelegation(): void
    {
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote(WorkspaceInvitationVoter::WORKSPACE_EDIT, canEditCabinet: true));
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote(WorkspaceInvitationVoter::WORKSPACE_EDIT, canEditCabinet: false));
    }

    public function testPermissionsManagementIsAdminOnly(): void
    {
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote(WorkspaceInvitationVoter::PERMISSIONS_MANAGE, isAdmin: true));
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote(WorkspaceInvitationVoter::PERMISSIONS_MANAGE, canInvite: true, canEditCabinet: true, isAdmin: false));
    }
}
