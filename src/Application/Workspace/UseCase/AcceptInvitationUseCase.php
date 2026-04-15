<?php

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Request\WorkspaceMemberRequest;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Enum\InvitationStatus;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Webmozart\Assert\Assert;

readonly class AcceptInvitationUseCase
{
    public function __construct(
        private WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository,
        private UserRepositoryInterface $userRepository,
        private Security $security,
        private LoggerInterface $logger,
        private SaveWorkspaceMemberUseCase $saveWorkspaceMemberUseCase,
    ) {}

    public function __invoke(string $invitationSlugId): void
    {
        Assert::notNull($invitationSlugId);
        $invitation = $this->workspaceInvitationRepository->findBySlugId($invitationSlugId);
        Assert::notNull($invitation);

        Assert::notNull($invitation->email);
        Assert::notNull($invitation->firstName);
        Assert::notNull($invitation->lastName);
        Assert::notNull($invitation->invitedRole);

        $workspace = $invitation->workspace;
        Assert::notNull($workspace);
        Assert::notNull($workspace->slugId);

        try {
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
            $invitation->invitationStatus = InvitationStatus::ACCEPTED;
            $this->workspaceInvitationRepository->save($invitation);
            $this->userRepository->save($user);

            $member = new WorkspaceMemberRequest();
            $member->workspaceSlugId = $workspace->slugId;
            $member->userSlugId = $user->slugId;
            $member->role = $invitation->invitedRole;

            ($this->saveWorkspaceMemberUseCase)($member);
            $this->security->login(
                $user,
                'security.authenticator.form_login.main',
                'main'
            );
        } catch (\Exception $e) {
            $this->logger->error('Erreur métier lors de la création d\'une invitation', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
