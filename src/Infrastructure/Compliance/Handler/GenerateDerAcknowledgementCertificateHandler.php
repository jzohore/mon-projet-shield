<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Handler;

use App\Domain\Compliance\Repository\DerAcknowledgementRepositoryInterface;
use App\Domain\Port\DocumentStorageInterface;
use App\Infrastructure\Compliance\Message\GenerateDerAcknowledgementCertificateMessage;
use App\Infrastructure\Pdf\PdfGeneratorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

/**
 * Produit l'attestation PDF d'accusé de réception du DER (« signé
 * électroniquement » côté client) : identité déclarée, horodatage, IP, empreinte
 * SHA-256 du DER, texte de l'attestation accepté.
 */
#[AsMessageHandler]
readonly class GenerateDerAcknowledgementCertificateHandler
{
    public function __construct(
        private DerAcknowledgementRepositoryInterface $acknowledgementRepository,
        private PdfGeneratorInterface $pdfGenerator,
        private DocumentStorageInterface $storage,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(GenerateDerAcknowledgementCertificateMessage $message): void
    {
        $acknowledgement = $this->acknowledgementRepository->findBySlugId($message->acknowledgementSlugId);
        Assert::notNull($acknowledgement, 'Accusé de réception introuvable pour la génération de l\'attestation.');

        if ($acknowledgement->hasCertificate()) {
            return;
        }

        $tempFilePath = null;

        try {
            $pdfContent = $this->pdfGenerator->generateFromHtml('@app/pdf/der_acknowledgement_certificate.html.twig', [
                'acknowledgement' => $acknowledgement,
                'folder' => $acknowledgement->document->folder,
            ]);

            if ('' === $pdfContent || !str_starts_with($pdfContent, '%PDF-')) {
                throw new \RuntimeException('Gotenberg n\'a pas renvoyé un PDF valide pour l\'attestation.');
            }

            $tempFilePath = sys_get_temp_dir() . '/' . uniqid('der_ack_cert_', true) . '.pdf';
            file_put_contents($tempFilePath, $pdfContent);

            $fileToStore = new UploadedFile(
                path: $tempFilePath,
                originalName: sprintf('attestation_der_%s.pdf', $acknowledgement->slugId),
                mimeType: 'application/pdf',
                test: true,
            );

            $directory = sprintf('documents/der/%s/certificate', $acknowledgement->document->folder->slugId);
            $storagePath = $this->storage->store($fileToStore, $directory);

            $acknowledgement->attachCertificate($storagePath, hash('sha256', $pdfContent));
            $this->acknowledgementRepository->save($acknowledgement);
        } catch (\Throwable $e) {
            $this->logger->error('Échec de génération de l\'attestation d\'accusé de réception du DER.', [
                'acknowledgement_slug_id' => $message->acknowledgementSlugId,
                'error' => $e::class,
            ]);

            throw $e; // retry Messenger
        } finally {
            if (null !== $tempFilePath && file_exists($tempFilePath)) {
                @unlink($tempFilePath);
            }
        }
    }
}
