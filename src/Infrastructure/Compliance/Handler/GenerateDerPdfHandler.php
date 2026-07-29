<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Handler;

use App\Application\Compliance\UseCase\ComplianceFolder\ComplianceFolderShowAssembler;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Port\DocumentStorageInterface;
use App\Domain\Shared\Port\RealTimeNotifierInterface;
use App\Infrastructure\Compliance\Message\GenerateDerPdfMessage;
use App\Infrastructure\Pdf\PdfGeneratorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
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
    ) {
    }

    public function __invoke(GenerateDerPdfMessage $message): void
    {
        $documentUuid = Uuid::fromString($message->documentId);
        $document = $this->documentRepository->findById($documentUuid);
        Assert::notNull($document);

        $tempFilePath = null;

        try {
            if ($message->oldStoragePath) {
                $this->storage->delete($message->oldStoragePath);
            }

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

            $document->markAsGenerated($finalStoragePath);
            $this->documentRepository->save($document);
        } catch (\Throwable $e) {
            $document->markAsFailed();
            $this->documentRepository->save($document);
            $this->logger->error('Erreur métier', ['error' => $e->getMessage()]);

            throw $e; // Déclenche le Retry Messenger
        } finally {
            // 6. Nettoyage sécurisé
            if (null !== $tempFilePath && file_exists($tempFilePath)) {
                @unlink($tempFilePath);
            }
        }

        $this->notifier->notify(
            topic: 'folder_' . $document->folder->slugId,
            payload: ['action' => 'new_document_der']
        );
    }
}
