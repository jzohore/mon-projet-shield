<?php

namespace App\Infrastructure\Ocr\Handler;

use App\Domain\Kyc\Validator\DocumentValidator;
use App\Domain\Port\DocumentStorageInterface;
use App\Domain\Port\OcrProviderInterface;
use App\Infrastructure\KYC\Persistence\KycDocumentRepository;
use App\Infrastructure\Ocr\Message\ProcessOcrMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final readonly class ProcessOcrMessageHandler
{
    public function __construct(
        private KycDocumentRepository $repository,
        private OcrProviderInterface $ocrProvider,
        private DocumentStorageInterface $storage,
        private DocumentValidator $validator,
    ) {}

    public function __invoke(ProcessOcrMessage $message): void
    {
        // 1. Récupération & Assertion
        $doc = $this->repository->findBySlugId($message->documentSlugId);
        Assert::notNull($doc, sprintf('Document introuvable pour le slug: %s', $message->documentSlugId));

        // 2. Chemin du fichier & Assertion
        Assert::notNull($doc->storagePath, 'Le chemin du document est requis.');
        $fullPath = $this->storage->getTemporaryUrl($doc->storagePath);
        Assert::notNull($fullPath, 'Impossible de générer une URL temporaire pour le document.');

        // 3. Extraction OCR
        $extractedData = $this->ocrProvider->extractData(
            $doc->type,
            $fullPath
        );

        // 4. Vérification métier : L'OCR a-t-il vraiment lu quelque chose ?
        // array_filter enlève les valeurs nulles. S'il ne reste rien, l'extraction a échoué.
        if (empty(array_filter($extractedData, fn($value) => !empty($value)))) {
            $doc->reject("Le document est illisible ou ne correspond pas au type attendu.");
        } else {
            // 1. On sauvegarde temporairement les datas extraites dans l'entité
            $doc->setExtractedData($extractedData);

            // 2. On passe le document au scanner de conformité
            $validationErrors = $this->validator->validate($doc);

            if (count($validationErrors) > 0) {
                // Rejet automatique avec toutes les raisons combinées
                $doc->reject(implode("\n", $validationErrors));
            } else {
                // Tout est parfait !
                $doc->markAsProcessed(); // Ou markAsValid() selon ton workflow
            }
        }

        // 5. Sauvegarde finale (Succès ou Rejet)
        $this->repository->save($doc);
    }
}
