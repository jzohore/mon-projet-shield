<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Handler;

use App\Application\Compliance\UseCase\ComplianceFolder\ComplianceFolderShowAssembler;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Event\DerPdfGeneratedEvent;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Port\DocumentStorageInterface;
use App\Domain\Shared\Port\RealTimeNotifierInterface;
use App\Infrastructure\Compliance\Message\GenerateDerPdfMessage;
use App\Infrastructure\Pdf\PdfGeneratorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
readonly class GenerateDerPdfHandler
{
    public function __construct(
        private ComplianceDocumentRepositoryInterface $documentRepository,
        private PdfGeneratorInterface $pdfGenerator,
        private LoggerInterface $logger,
        private DocumentStorageInterface $storage,
        private RealTimeNotifierInterface $notifier,
        private ComplianceFolderShowAssembler $complianceFolderShowAssembler,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(GenerateDerPdfMessage $message): void
    {
        $documentUuid = Uuid::fromString($message->documentId);
        $document = $this->documentRepository->findById($documentUuid);
        Assert::notNull($document);

        $tempFilePath = null;

        try {
            $folder = $this->complianceFolderShowAssembler->assemble($document->folder);
            // 1. Appel au générateur (qui gère la requête HTTP proprement)
            $pdfContent = $this->pdfGenerator->generateFromHtml('@app/pdf/der_template.html.twig', [
                'document' => $document,
                'folder' => $folder,
            ]);

            // 2. LE BOUCLIER ANTI-TXT
            if ('' === $pdfContent || '0' === $pdfContent) {
                throw new \Exception('Le générateur PDF a renvoyé un contenu vide.');
            }

            if (!str_starts_with($pdfContent, '%PDF-')) {
                throw new \Exception("Gotenberg n'a pas renvoyé un PDF valide. Reçu : " . substr($pdfContent, 0, 100));
            }

            // 3. Fichier physique temporaire
            $tempFilePath = sys_get_temp_dir() . '/' . uniqid('screening_', true) . '.pdf';
            file_put_contents($tempFilePath, $pdfContent);

            // 4. Faux UploadedFile
            $fileToStore = new UploadedFile(
                path: $tempFilePath,
                originalName: sprintf('document_der_%s.pdf', str_replace(' ', '_', $document->folder->reference)),
                mimeType: 'application/pdf',
                test: true
            );

            // 5. Upload S3 et Sauvegarde
            $directory = sprintf('documents/der/%s', $document->folder->slugId);
            $finalStoragePath = $this->storage->store($fileToStore, $directory);

            // 6. Le nouveau PDF est en place : on peut acter la génération. La
            //    suppression de l'ancien fichier ne vient qu'APRÈS, et seulement
            //    s'il n'est référencé par aucun accusé de réception (même
            //    révoqué) — sinon on détruirait une preuve légale.
            $document->markAsGenerated($finalStoragePath);
            $this->documentRepository->save($document);

            if (null !== $message->oldStoragePath && !$this->isStoragePathReferencedByAnAcknowledgement($document, $message->oldStoragePath)) {
                $this->storage->delete($message->oldStoragePath);
            }

            Assert::notNull($document->id);
            $this->eventDispatcher->dispatch(new DerPdfGeneratedEvent($document->id->toString()));
        } catch (\DomainException $e) {
            // Garde métier définitive (ex: un accusé de réception est arrivé entre
            // temps, cf. ComplianceDocument::guardNotSealed()) : aucun retry n'y
            // changera rien, et on n'a rien détruit — l'ancien PDF (celui de la
            // preuve) reste intact.
            $this->logger->warning('Génération de DER refusée par une garde métier.', [
                'document_id' => $message->documentId,
                'error' => $e->getMessage(),
            ]);

            throw new UnrecoverableMessageHandlingException($e->getMessage(), $e->getCode(), previous: $e);
        } catch (\Throwable $e) {
            $document->markAsFailed();
            $this->documentRepository->save($document);
            $this->logger->error('Erreur métier', ['error' => $e->getMessage()]);

            throw $e; // Déclenche le Retry Messenger
        } finally {
            // 7. Nettoyage sécurisé
            if (null !== $tempFilePath && file_exists($tempFilePath)) {
                @unlink($tempFilePath);
            }
        }

        $this->notifier->notify(
            topic: 'folder_' . $document->folder->slugId,
            payload: ['action' => 'new_document_der']
        );
    }

    /**
     * Un PDF référencé par un accusé de réception (en vigueur ou révoqué) est
     * une preuve : il ne doit jamais être supprimé du stockage.
     */
    private function isStoragePathReferencedByAnAcknowledgement(ComplianceDocument $document, string $storagePath): bool
    {
        foreach ($document->acknowledgements as $acknowledgement) {
            if ($acknowledgement->pdfStoragePath === $storagePath) {
                return true;
            }
        }

        return false;
    }
}
