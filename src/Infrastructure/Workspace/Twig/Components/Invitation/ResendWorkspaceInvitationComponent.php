<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Twig\Components\Invitation;

use App\Application\Workspace\UseCase\Invitation\ResendWorkspaceInvitationUseCase;
use App\Domain\Shared\Exception\AbstractDomainException;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Infrastructure\Shared\Component\LiveFlashTrait;
use App\Infrastructure\Workspace\Voter\WorkspaceInvitationVoter;
use Psr\Log\LoggerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'ResendWorkspaceInvitationComponent',
    template: 'components/Workspace/ResendWorkspaceInvitationComponent.html.twig',
)]
class ResendWorkspaceInvitationComponent
{
    use DefaultActionTrait;
    use LiveFlashTrait;

    #[LiveProp]
    public WorkspaceInvitation $workspaceInvitation;

    public function __construct(
        private readonly ResendWorkspaceInvitationUseCase $resendWorkspaceInvitationUseCase,
        private readonly LoggerInterface $logger,
        private readonly RateLimiterFactory $workspaceInvitationResendLimiter,
    ) {
    }

    #[LiveAction]
    #[IsGranted(WorkspaceInvitationVoter::RESEND, subject: 'workspaceInvitation')]
    public function resendInvitation(): void
    {
        $this->clearLiveFlash();

        // Un renvoi régénère un jeton d'accès et part par e-mail : borné par invitation.
        $limit = $this->workspaceInvitationResendLimiter->create($this->workspaceInvitation->slugId)->consume();
        if (!$limit->isAccepted()) {
            $this->addLiveFlash('error', 'Vous avez renvoyé cette invitation trop souvent. Réessayez plus tard.');

            return;
        }

        try {
            ($this->resendWorkspaceInvitationUseCase)($this->workspaceInvitation);

            $this->addLiveFlash('success', 'L\'invitation a été envoyée de nouveau.');
        } catch (AbstractDomainException $e) {
            $this->logger->error('Tentative de révocation échouée', [
                'email' => $this->workspaceInvitation->email,
                'error' => $e->getMessage(),
            ]);

            $this->addLiveFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical('Crash système lors de la création d\'une invitation', [
                'email' => $this->workspaceInvitation->email,
                'error' => $e->getMessage(),
            ]);

            $this->addLiveFlash('error', 'Une erreur technique est survenue. Veuillez réessayer plus tard.');
        }
    }
}
