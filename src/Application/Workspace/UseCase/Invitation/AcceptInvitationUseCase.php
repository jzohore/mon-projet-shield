<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\Invitation;

use App\Domain\Database\TransactionManagerInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\WorkspaceMember;
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

        if (!$invitation instanceof \App\Domain\Workspace\Entity\WorkspaceInvitation) {
            throw InvitationNotFoundException::withSlugId($invitationSlugId);
        }

        $workspace = $invitation->workspace;
        Assert::notNull($workspace->slugId);

        $user = User::create(
            email: $invitation->email,
            firstName: $invitation->firstName,
            lastName: $invitation->lastName,
            isVerified: true,
            roles: [$invitation->invitedRole->value],
            onboardingStatus: OnboardingStatus::COMPLETED,
            isActif: true,
        );

        $invitation->clearMagicLinkToken();
        $invitation->accept();

        // On instancie l'entité directement (adapte selon ton constructeur/factory)
        $member = WorkspaceMember::create($workspace, $user, $invitation->invitedRole);

        $this->transactionManager->transactional(function () use ($invitation, $user, $member): void {
            $this->userRepository->save($user, false);
            $this->workspaceInvitationRepository->save($invitation, false);
            $this->workspaceMemberRepository->save($member, false);
        });

        return $user;
    }
}
