<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Service;

use App\Application\Compliance\UseCase\ComplianceFolder\SaveGeminiAnalysisUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\MeetingRecording;
use App\Domain\Compliance\Event\MeetingAnalysisCompletedEvent;
use App\Domain\Compliance\Exception\CannotAttachGeminiOutputException;
use App\Domain\Port\DocumentStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Webmozart\Assert\Assert;

final readonly class GeminiMeetingAnalyzer implements MeetingAnalyzerInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $geminiApiKey,
        private SaveGeminiAnalysisUseCase $saveGeminiAnalysisUseCase,
        private LoggerInterface $logger,
        private EventDispatcherInterface $eventDispatcher,
        private DocumentStorageInterface $storage,
    ) {
    }

    public function analyzeCompleteMeeting(MeetingRecording $recording): void
    {
        $folder = $recording->complianceFolder;
        $audioFilePath = $this->storage->getTemporaryUrl($recording->s3Path);

        Assert::stringNotEmpty($audioFilePath, 'Aucune URL de fichier audio fournie pour l\'analyse.');

        try {
            $response = $this->httpClient->request('GET', $audioFilePath, ['timeout' => 60]);
            $rawContent = $response->getContent();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Impossible de télécharger le fichier audio depuis le stockage sécurisé.', $e->getCode(), previous: $e);
        }

        Assert::stringNotEmpty($rawContent, 'Le fichier audio téléchargé est vide.');

        $audioContent = base64_encode($rawContent);

        // 🚀 FIX 3 : Déduction dynamique du MimeType depuis l'extension finale S3
        $extension = pathinfo($recording->s3Path, \PATHINFO_EXTENSION);
        $dynamicMimeType = match ($extension) {
            'mp4', 'm4a' => 'audio/mp4',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            default => 'audio/webm',
        };

        $isKyb = $folder instanceof BusinessFolder;
        $systemInstruction = $isKyb
            ? "Tu analyses un entretien d'une PERSONNE MORALE (Holding/Entreprise). Extrais la structure, les UBO, l'origine des fonds."
            : "Tu analyses un entretien d'une PERSONNE PHYSIQUE. Extrais la situation familiale, patrimoniale et les objectifs.";

        $prompt = <<<TEXT
            Tu es un expert en conformité réglementaire AMF.
            {$systemInstruction}

            RÈGLES ABSOLUES ET IMPÉRATIVES (EXTRACTION PURE) :
            1. Base-toi UNIQUEMENT sur les propos tenus dans cet audio précis.
            2. INTERDICTION STRICTE d'inventer des données ou de déduire des informations non verbalisées. Ne suppose rien.
            3. Si l'audio est un test, hors sujet ou vide de données financières, écris "Test ou hors sujet" et laisse les tableaux vides.
            4. Tu ne dois PAS chercher à fusionner avec un historique. Ton rôle est uniquement d'extraire les faits nouveaux de cette session.

            Format STRICTEMENT JSON pur :
            {
              "executiveSummary": "Synthèse factuelle stricte de CE fragment audio uniquement.",
              "riskProfileDetected": "Prudent/Equilibré/Dynamique ou 'Non déterminé'",
              "kycUpdates": ["Liste uniquement les faits EXPLICITEMENT prononcés dans cet audio"],
              "actionPlan": ["Action à mener suite à cet audio", "Laisse vide si rien à faire"]
            }
            TEXT;

        $url = sprintf('https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key=%s', $this->geminiApiKey);

        $response = $this->httpClient->request('POST', $url, [
            'json' => [
                'contents' => [[
                    'parts' => [
                        ['text' => $prompt],
                        // 🚀 Utilisation du bon mime-type pour éviter le rejet de Google
                        ['inline_data' => ['mime_type' => $dynamicMimeType, 'data' => $audioContent]],
                    ],
                ]],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                    'temperature' => 0.0,
                ],
            ],
            'timeout' => 120,
        ]);

        $data = $response->toArray();
        $result = json_decode($data['candidates'][0]['content']['parts'][0]['text'] ?? '{}', true);

        try {
            ($this->saveGeminiAnalysisUseCase)($recording, $result);
        } catch (CannotAttachGeminiOutputException) {
            $this->logger->info('Analyse déjà présente, on stoppe le traitement en doublon.', [
                'recording_slug_id' => $recording->slugId,
            ]);

            return;
        }
        Assert::notNull($recording->id);
        $this->eventDispatcher->dispatch(new MeetingAnalysisCompletedEvent(
            recordingId: $recording->id->toRfc4122(),
            folderSlugId: $folder->slugId
        ));
    }
}
