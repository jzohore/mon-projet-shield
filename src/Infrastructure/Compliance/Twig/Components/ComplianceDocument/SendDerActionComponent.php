<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Twig\Components\ComplianceDocument;

use App\Application\Compliance\UseCase\ComplianceDocument\DER\SendDerToClientUseCase;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Shared\Exception\AbstractDomainException;
use App\Infrastructure\Shared\Component\LiveFlashTrait;
use Psr\Log\LoggerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponder;

#[AsLiveComponent(
    name: 'SendDerActionComponent',
    template: 'components/Compliance/ComplianceDocument/SendDerActionComponent.html.twig',
)]
class SendDerActionComponent
{
    use DefaultActionTrait;
    use LiveFlashTrait;

    #[LiveProp]
    public ComplianceFolder $folder;

    public function __construct(
        private readonly SendDerToClientUseCase $sendDerUseCase,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[LiveAction]
    public function sendToClient(LiveResponder $responder): void
    {
        try {
            // Lancement du Use Case
            ($this->sendDerUseCase)($this->folder);

            // Feedback positif

            // On peut émettre un event pour rafraîchir la page ou fermer le panel
            $responder->emit('derStatusUpdated');
        } catch (AbstractDomainException $e) {
            $this->logger->warning('Tentative d\'envoie du DER bloquée.', [
                'folder_slug' => $this->folder->slugId,
                'error' => $e->getMessage(),
            ]);

            $this->addLiveFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Crash système lors de l\'envoi du DER.', [
                'folder_slug' => $this->folder->slugId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addLiveFlash('error', $e->getMessage());
        }
    }
}
