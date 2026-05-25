<?php

namespace App\Infrastructure\Workspace\Twig\Components\Invitation;

use App\Application\Workspace\UseCase\Invitation\RevokeWorkspaceInvitationUseCase;
use App\Domain\Shared\Exception\AbstractDomainException;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Infrastructure\Shared\Component\LiveFlashTrait;
use Exception;
use Psr\Log\LoggerInterface;
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
    public function __construct(
        private readonly RevokeWorkspaceInvitationUseCase $revokeWorkspaceInvitationUseCase,
        private readonly LoggerInterface $logger,
    ) {}

    #[LiveAction]
    public function revokeInvitation(LiveResponder $liveResponder): void
    {
        $this->clearLiveFlash();

        try {
            ($this->revokeWorkspaceInvitationUseCase)($this->workspaceInvitation);

            $this->addLiveFlash('success', 'L\'invitation a été annulée.');
            $liveResponder->emitUp('revoke_invitation');
        } catch (AbstractDomainException $e) {
            $this->logger->error('Tentative de révocation échouée', [
                'email' => $this->workspaceInvitation->email,
                'error' => $e->getMessage(),
            ]);

            $this->addLiveFlash('error', $e->getMessage());
        } catch (Exception $e) {
            $this->logger->critical('Crash système lors de la création d\'une invitation', [
                'email' => $this->workspaceInvitation->email,
                'error' => $e->getMessage(),
            ]);

            $this->addLiveFlash('error', 'Une erreur technique est survenue. Veuillez réessayer plus tard.');
        }
    }
}
