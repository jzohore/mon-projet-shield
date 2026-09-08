<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\Invitation;

use App\Domain\Database\TransactionManagerInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Exception\InvitationAlreadyUsedException;
use App\Domain\Workspace\Exception\InvitationNotFoundException;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use Webmozart\Assert\Assert;

readonly class AcceptInvitationUseCase
{
    public function __construct(
        private WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository,
        private UserRepositoryInterface $userRepository,
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(string $invitationSlugId): User
    {
        $invitation = $this->workspaceInvitationRepository->findBySlugId($invitationSlugId);

        if (!$invitation instanceof WorkspaceInvitation) {
            throw InvitationNotFoundException::withSlugId($invitationSlugId);
        }

        // 🛡️ Idempotence : une invitation déjà acceptée / expirée / révoquée
        // ne doit pas recréer un compte ou un second rattachement.
        if (!$invitation->isPending() || !$invitation->isMagicLinkTokenValid()) {
            throw InvitationAlreadyUsedException::create();
        }

        $workspace = $invitation->workspace;
        Assert::notNull($workspace->slugId);

        $existingUser = $this->userRepository->findByEmail($invitation->email);

        if ($existingUser instanceof User) {
            return $this->attachExistingUser($invitation, $existingUser);
        }

        return $this->createUserFromInvitation($invitation);
    }

    private function attachExistingUser(WorkspaceInvitation $invitation, User $user): User
    {
        $workspace = $invitation->workspace;
        $alreadyMember = $this->workspaceMemberRepository->findByWorkspaceAndUser($workspace, $user);

        $invitation->accept();
        $invitation->clearMagicLinkToken();

        $this->transactionManager->transactional(function () use ($invitation, $user, $workspace, $alreadyMember): void {
            if (!$alreadyMember instanceof WorkspaceMember) {
                $this->workspaceMemberRepository->save(
                    WorkspaceMember::create($workspace, $user, $invitation->invitedRole),
                    false,
                );
            }
            $this->workspaceInvitationRepository->save($invitation, false);
        });

        return $user;
    }

    private function createUserFromInvitation(WorkspaceInvitation $invitation): User
    {
        $workspace = $invitation->workspace;

        $user = User::create(
            email: $invitation->email,
            firstName: $invitation->firstName,
            lastName: $invitation->lastName,
            isVerified: true,
            roles: [$invitation->invitedRole->value],
            onboardingStatus: OnboardingStatus::COMPLETED,
            isActif: true,
        );

        $invitation->accept();
        $invitation->clearMagicLinkToken();

        $member = WorkspaceMember::create($workspace, $user, $invitation->invitedRole);

        $this->transactionManager->transactional(function () use ($invitation, $user, $member): void {
            $this->userRepository->save($user, false);
            $this->workspaceInvitationRepository->save($invitation, false);
            $this->workspaceMemberRepository->save($member, false);
        });

        return $user;
    }
}
