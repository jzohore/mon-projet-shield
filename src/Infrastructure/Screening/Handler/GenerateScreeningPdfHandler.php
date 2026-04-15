<?php

namespace App\Infrastructure\Screening\Handler;

use App\Domain\Port\DocumentStorageInterface;
use App\Domain\Screening\Repository\ScreeningAuditRepositoryInterface;
use App\Infrastructure\Pdf\PdfGeneratorInterface;
use App\Infrastructure\Screening\Message\GenerateScreeningPdfMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

// Tes propres services à adapter

#[AsMessageHandler]
readonly class GenerateScreeningPdfHandler
{
    public function __construct(
        private DocumentStorageInterface $storage,
        private ScreeningAuditRepositoryInterface $auditRepository,
        private PdfGeneratorInterface $pdfGenerator,
        private LoggerInterface $logger,
    ) {}


    public function __invoke(GenerateScreeningPdfMessage $message): void
    {
        $audit = $this->auditRepository->findOneBySlug($message->auditId);
        Assert::notNull($audit, sprintf('Audit introuvable pour le slug: %s', $message->auditId));

        $tempFilePath = null;

        try {
            if ($message->oldStoragePath) {
                $this->storage->delete($message->oldStoragePath);
            }
            // 1. Appel au générateur (qui gère la requête HTTP proprement)
            $pdfContent = $this->pdfGenerator->generateFromHtml('@app/pdf/screening_certificate.html.twig', [
                'audit' => $audit,
            ]);

            // 2. LE BOUCLIER ANTI-TXT
            if (empty($pdfContent)) {
                throw new \Exception("Le générateur PDF a renvoyé un contenu vide.");
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
                originalName: sprintf('certificat_recherche_%s.pdf', str_replace(' ', '_', $audit->query)),
                mimeType: 'application/pdf',
                error: null,
                test: true
            );

            // 5. Upload S3 et Sauvegarde
            $directory = sprintf('screening/%s', $audit->workspace->slugId);
            $finalStoragePath = $this->storage->store($fileToStore, $directory);

            $audit->markAsGenerated($finalStoragePath);
            $this->auditRepository->save($audit);

        } catch (\Throwable $e) {
            $audit->markAsFailed();
            $this->auditRepository->save($audit);
            $this->logger->error('Erreur métier', ['error' => $e->getMessage()]);

            throw $e; // Déclenche le Retry Messenger

        } finally {
            // 6. Nettoyage sécurisé
            if ($tempFilePath !== null && file_exists($tempFilePath)) {
                @unlink($tempFilePath);
            }
        }
    }
}
