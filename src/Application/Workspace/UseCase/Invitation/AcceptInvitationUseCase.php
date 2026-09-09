<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\Invitation;

use App\Domain\Database\TransactionManagerInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Event\WorkspaceInvitationAcceptedEvent;
use App\Domain\Workspace\Exception\CannotAcceptInvitationException;
use App\Domain\Workspace\Exception\InvitationAlreadyUsedException;
use App\Domain\Workspace\Exception\InvitationNotFoundException;
use App\Domain\Workspace\Exception\SeatLimitReachedException;
use App\Domain\Workspace\Exception\UserAlreadyBelongsToAnotherWorkspaceException;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\SeatAvailability;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

readonly class AcceptInvitationUseCase
{
    public function __construct(
        private WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository,
        private UserRepositoryInterface $userRepository,
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private TransactionManagerInterface $transactionManager,
        private EventDispatcherInterface $eventDispatcher,
        private SeatAvailability $seatAvailability,
    ) {
    }

    /**
     * @param string|null $authenticatedEmail identité déjà connectée sur le firewall « main », le cas échéant
     */
    public function __invoke(string $invitationSlugId, ?string $authenticatedEmail = null): User
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

        // 🛡️ On ne consomme pas l'invitation de quelqu'un d'autre : si une session
        // « main » est déjà ouverte, elle doit correspondre au destinataire.
        if (null !== $authenticatedEmail
            && !hash_equals(mb_strtolower($invitation->email), mb_strtolower($authenticatedEmail))) {
            throw CannotAcceptInvitationException::recipientMismatch();
        }

        $workspace = $invitation->workspace;
        Assert::notNull($workspace->slugId);

        // 🛡️ Security::login() court-circuite le user_checker : on rejoue ses gardes ici.
        if (!$workspace->isActive) {
            throw CannotAcceptInvitationException::workspaceSuspended();
        }

        $this->assertSeatAvailable($workspace);

        $existingUser = $this->userRepository->findByEmail($invitation->email);

        if ($existingUser instanceof User) {
            return $this->attachExistingUser($invitation, $existingUser);
        }

        return $this->createUserFromInvitation($invitation);
    }

    /**
     * Le plafond peut avoir baissé entre l'envoi et l'acceptation (downgrade
     * Stripe, abonnement expiré, retour à l'essai). L'invitation est encore
     * PENDING, donc déjà comptée : on ne bloque que si le cabinet est en dépassement.
     */
    private function assertSeatAvailable(Workspace $workspace): void
    {
        $used = $this->seatAvailability->usedSeats($workspace);
        $allowed = $this->seatAvailability->allowedSeats($workspace);

        if ($used > $allowed) {
            throw SeatLimitReachedException::forWorkspace(usedSeats: $used, allowedSeats: $allowed);
        }
    }

    private function attachExistingUser(WorkspaceInvitation $invitation, User $user): User
    {
        if (!$user->isActif) {
            throw CannotAcceptInvitationException::accountDisabled();
        }

        $workspace = $invitation->workspace;

        // 🛡️ Modèle « un utilisateur = un espace de travail » : on refuse un
        // rattachement croisé, qui casserait la résolution du workspace courant.
        foreach ($this->workspaceMemberRepository->findByUser($user) as $membership) {
            if ($membership->workspace->slugId !== $workspace->slugId) {
                throw UserAlreadyBelongsToAnotherWorkspaceException::create();
            }
        }

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

        $this->eventDispatcher->dispatch(new WorkspaceInvitationAcceptedEvent($invitation, $workspace, $user, false));

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

        $this->eventDispatcher->dispatch(new WorkspaceInvitationAcceptedEvent($invitation, $workspace, $user, true));

        return $user;
    }
}
