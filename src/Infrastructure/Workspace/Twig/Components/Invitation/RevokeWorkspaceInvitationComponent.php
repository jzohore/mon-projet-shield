<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Twig\Components\Invitation;

use App\Application\Workspace\UseCase\Invitation\RevokeWorkspaceInvitationUseCase;
use App\Domain\Shared\Exception\AbstractDomainException;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Enum\InvitationRevocationReason;
use App\Infrastructure\Shared\Component\LiveFlashTrait;
use App\Infrastructure\Workspace\Voter\WorkspaceInvitationVoter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponder;

#[AsLiveComponent(
    name: 'RevokeWorkspaceInvitationComponent',
    template: 'components/Workspace/RevokeWorkspaceInvitationComponent.html.twig',
)]
class RevokeWorkspaceInvitationComponent
{
    use DefaultActionTrait;
    use LiveFlashTrait;

    #[LiveProp]
    public WorkspaceInvitation $workspaceInvitation;

    #[LiveProp(writable: true)]
    public string $revocationReason = '';

    public function __construct(
        private readonly RevokeWorkspaceInvitationUseCase $revokeWorkspaceInvitationUseCase,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<int, InvitationRevocationReason>
     */
    public function reasons(): array
    {
        return InvitationRevocationReason::cases();
    }

    #[LiveAction]
    #[IsGranted(WorkspaceInvitationVoter::REVOKE, subject: 'workspaceInvitation')]
    public function revokeInvitation(LiveResponder $liveResponder): void
    {
        $this->clearLiveFlash();

        $reason = InvitationRevocationReason::tryFrom($this->revocationReason);
        if (!$reason instanceof InvitationRevocationReason) {
            $this->addLiveFlash('error', 'Sélectionnez un motif d\'annulation.');

            return;
        }

        try {
            ($this->revokeWorkspaceInvitationUseCase)($this->workspaceInvitation, $reason);

            // Le succès est notifié par le composant parent : cette ligne (et son
            // toast) disparaît au re-rendu déclenché par l'événement.
            $liveResponder->emitUp('revoke_invitation', ['email' => $this->workspaceInvitation->email]);
        } catch (AbstractDomainException $e) {
            $this->logger->error('Tentative de révocation échouée', [
                'email' => $this->workspaceInvitation->email,
                'error' => $e->getMessage(),
            ]);

            $this->addLiveFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical('Crash système lors de l\'annulation d\'une invitation', [
                'email' => $this->workspaceInvitation->email,
                'error' => $e->getMessage(),
            ]);

            $this->addLiveFlash('error', 'Une erreur technique est survenue. Veuillez réessayer plus tard.');
        }
    }
}
