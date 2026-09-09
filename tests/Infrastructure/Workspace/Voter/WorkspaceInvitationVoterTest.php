<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Workspace\Voter;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Infrastructure\Workspace\Voter\WorkspaceInvitationVoter;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class WorkspaceInvitationVoterTest extends TestCase
{
    use ReflectionHelperTrait;

    private function vote(bool $isAdmin, bool $collabCanInvite, bool $isMember, string $attribute = WorkspaceInvitationVoter::CREATE): int
    {
        $user = $this->createEntityState(User::class, ['email' => 'u@x.fr']);
        $workspace = $this->createEntityState(Workspace::class, [
            'name' => 'Cabinet',
            'slugId' => 'wrk_1',
            'collabCanInvite' => $collabCanInvite,
        ]);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $adm = $this->createStub(AccessDecisionManagerInterface::class);
        $adm->method('decide')->willReturn(false); // pas super-admin KYSURE

        $members = $this->createStub(WorkspaceMemberRepositoryInterface::class);
        $members->method('isUserAdminOfWorkspace')->willReturn($isAdmin);
        $members->method('findByWorkspaceAndUser')->willReturn(
            $isMember ? $this->createEntityState(WorkspaceMember::class, []) : null,
        );

        return new WorkspaceInvitationVoter($adm, $members)->vote($token, $workspace, [$attribute]);
    }

    public function testAdminIsAlwaysGranted(): void
    {
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote(isAdmin: true, collabCanInvite: false, isMember: true));
    }

    public function testCollabDeniedWhenDelegationIsOff(): void
    {
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote(isAdmin: false, collabCanInvite: false, isMember: true));
    }

    public function testCollabGrantedWhenDelegationIsOnAndMember(): void
    {
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote(isAdmin: false, collabCanInvite: true, isMember: true));
    }

    public function testNonMemberDeniedEvenWhenDelegationIsOn(): void
    {
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote(isAdmin: false, collabCanInvite: true, isMember: false));
    }

    public function testPermissionsManagementIsNeverDelegated(): void
    {
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->vote(isAdmin: false, collabCanInvite: true, isMember: true, attribute: WorkspaceInvitationVoter::PERMISSIONS_MANAGE),
        );
    }
}
