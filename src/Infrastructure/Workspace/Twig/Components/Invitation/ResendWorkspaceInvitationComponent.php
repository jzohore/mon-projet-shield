<?php

namespace App\Infrastructure\Workspace\Twig\Components\Invitation;

use App\Application\Workspace\UseCase\Invitation\ResendWorkspaceInvitationUseCase;
use App\Domain\Shared\Exception\AbstractDomainException;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Infrastructure\Shared\Component\LiveFlashTrait;
use Exception;
use Psr\Log\LoggerInterface;
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
    ) {}

    #[LiveAction]
    public function resendInvitation(): void
    {
        $this->clearLiveFlash();

        try {

            ($this->resendWorkspaceInvitationUseCase)($this->workspaceInvitation);

            $this->addLiveFlash('success', 'L\'invitation a été envoyé de nouveau.');

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
