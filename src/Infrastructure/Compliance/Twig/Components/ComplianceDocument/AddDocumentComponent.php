<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Twig\Components\ComplianceDocument;

use App\Application\Compliance\UseCase\ComplianceDocument\AddDocumentUseCase;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Enum\DocumentType;
use App\Domain\Shared\Exception\AbstractDomainException;
use App\Infrastructure\Shared\Component\LiveFlashTrait;
use Psr\Log\LoggerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponder;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'AddDocumentComponent',
    template: 'components/Compliance/ComplianceDocument/AddDocumentComponent.html.twig',
)]
class AddDocumentComponent
{
    use DefaultActionTrait;
    use LiveFlashTrait;

    #[LiveProp(writable: false)]
    public ComplianceFolder $folder;

    #[LiveProp(writable: true)]
    public ?DocumentType $type = null;

    public function __construct(
        private readonly AddDocumentUseCase $addDocumentUseCase,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[LiveAction]
    public function addDocument(LiveResponder $liveResponder): void
    {
        $this->clearLiveFlash();
        Assert::notNull($this->type);
        try {
            ($this->addDocumentUseCase)($this->type, $this->folder);

            $liveResponder->emitUp('documentAdded');
            $this->logger->info('Nouveau document ajouté manuellement au dossier.', [
                'folder_slug' => $this->folder->slugId,
                'document_type' => $this->type->getLabel(),
            ]);
        } catch (AbstractDomainException $e) {
            $this->logger->warning('Tentative d\'ajout d\'un document en double.', [
                'folder_slug' => $this->folder->slugId,
                'document_type' => $this->type->getLabel(),
            ]);

            $this->addLiveFlash('warning', $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Erreur technique lors de l\'ajout d\'un document au dossier.', [
                'folder_slug' => $this->folder->slugId,
                'document_type' => $this->type->getLabel(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addLiveFlash('error', 'Une erreur est survenue lors de l\'ajout du document. Veuillez réessayer.');
        }
    }

    /**
     * @return array<DocumentType>
     */
    public function getDocumentType(): array
    {
        return DocumentType::cases();
    }
}
