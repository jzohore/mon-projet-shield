<?php

declare(strict_types=1);

namespace App\Tests\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Request\WorkspaceMemberRequest;
use App\Application\Workspace\UseCase\AcceptInvitationUseCase;
use App\Application\Workspace\UseCase\SaveWorkspaceMemberUseCase;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Enum\InvitationStatus;
use App\Domain\Workspace\Enum\Industry;
use App\Domain\Workspace\Enum\InvitedRole;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class AcceptInvitationUseCaseTest extends TestCase
{
    #[Test]
    public function it_accepts_the_invitation_creates_the_user_and_adds_workspace_membership(): void
    {
        $workspaceInvitationRepository = $this->createMock(WorkspaceInvitationRepositoryInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $security = $this->createMock(Security::class);
        $logger = $this->createStub(LoggerInterface::class);
        $saveWorkspaceMemberUseCase = $this->createMock(SaveWorkspaceMemberUseCase::class);

        $owner = $this->createUser('owner@example.com', 'Owner', 'User');
        $workspace = $this->createWorkspace();
        $invitation = $this->createInvitation($owner, $workspace);

        $workspaceInvitationRepository->expects(self::once())
            ->method('findBySlugId')
            ->with($invitation->slugId)
            ->willReturn($invitation);

        $workspaceInvitationRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(static function (object $savedInvitation): bool {
                return $savedInvitation instanceof WorkspaceInvitation
                    && $savedInvitation->invitationStatus === InvitationStatus::ACCEPTED
                    && null === $savedInvitation->magicLinkToken
                    && null === $savedInvitation->magicLinkTokenExpiresAt;
            }));

        $userRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(static function (User $user): bool {
                return in_array('ROLE_WORKSPACE_COLLAB', $user->roles, true)
                    && true === $user->isVerified
                    && true === $user->isActif;
            }));

        $saveWorkspaceMemberUseCase->expects(self::once())
            ->method('__invoke')
            ->with(self::callback(static function (WorkspaceMemberRequest $member): bool {
                return null !== $member->workspaceSlugId
                    && null !== $member->userSlugId
                    && InvitedRole::ROLE_WORKSPACE_COLLAB === $member->role;
            }));

        $security->expects(self::once())
            ->method('login');

        $useCase = new AcceptInvitationUseCase(
            $workspaceInvitationRepository,
            $userRepository,
            $security,
            $logger,
            $saveWorkspaceMemberUseCase,
        );

        $useCase($invitation->slugId);
    }

    private function createWorkspace(): Workspace
    {
        return Workspace::create(
            name: 'Cabinet Test',
            siret: '12345678901234',
            legalName: 'Cabinet Test SARL',
            address: '1 rue de Paris',
            industry: Industry::LAWYER
        );
    }

    private function createUser(string $email, string $firstName, string $lastName): User
    {
        return User::create(
            email: $email,
            firstName: $firstName,
            lastName: $lastName,
            isVerified: true,
            roles: ['ROLE_USER'],
            isActif: true,
        );
    }

    private function createInvitation(User $owner, Workspace $workspace): WorkspaceInvitation
    {
        return WorkspaceInvitation::create(
            owner: $owner,
            workspace: $workspace,
            email: 'invitee@example.com',
            firstName: 'Jane',
            lastName: 'Doe',
            invitedRole: InvitedRole::ROLE_WORKSPACE_COLLAB,
        );
    }
}
