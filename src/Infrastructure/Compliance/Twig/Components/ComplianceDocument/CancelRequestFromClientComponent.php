<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Twig\Components\ComplianceDocument;

use App\Application\Compliance\UseCase\ComplianceDocument\RequestFromClientUseCase;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Shared\Exception\AbstractDomainException;
use App\Infrastructure\Shared\Component\LiveFlashTrait;
use Psr\Log\LoggerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponder;

#[AsLiveComponent(
    name: 'CancelRequestFromClientComponent',
    template: 'components/Compliance/ComplianceDocument/CancelRequestFromClientComponent.html.twig',
)]
class CancelRequestFromClientComponent
{
    use DefaultActionTrait;
    use LiveFlashTrait;

    #[LiveProp(writable: false)]
    public ComplianceFolder $folder;

    #[LiveProp(writable: true)]
    public ?ComplianceDocument $document = null;

    public function __construct(
        private readonly RequestFromClientUseCase $requestFromClientUseCase,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[LiveAction]
    public function cancelClientRequest(#[LiveArg] string $documentId, LiveResponder $liveResponder): void
    {
        $this->clearLiveFlash();

        try {
            ($this->requestFromClientUseCase)($documentId, $this->folder, false);

            $liveResponder->emitUp('documentCancelRequested');
            $this->logger->info('Demande de document annulé avec succès.', [
                'folder_slug' => $this->folder->slugId,
                'document_id' => $documentId,
            ]);
        } catch (AbstractDomainException $e) {
            $this->logger->warning('Tentative d\'annulation de la demande de document bloquée.', [
                'folder_slug' => $this->folder->slugId,
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);

            $this->addLiveFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Crash système lors de l\'annulation de la demande d\'un document.', [
                'folder_slug' => $this->folder->slugId,
                'document_id' => $documentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addLiveFlash('error', 'Une erreur technique est survenue lors de la suppression.');
        }
    }
}
