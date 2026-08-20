<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Service;

use App\Application\Compliance\DTO\Request\HolisticMeetingReportDto;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceFolder;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Webmozart\Assert\Assert;

final readonly class GeminiMeetingAnalyzer implements MeetingAnalyzerInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $geminiApiKey,
    ) {
    }

    public function analyzeCompleteMeeting(ComplianceFolder $folder, string $audioFilePath): HolisticMeetingReportDto
    {
        Assert::stringNotEmpty($audioFilePath, 'Aucune URL de fichier audio fournie pour l\'analyse.');

        // 🛡️ Téléchargement via le client HTTP injecté plutôt que
        // file_get_contents() : indépendant du réglage allow_url_fopen
        // (souvent désactivé en prod pour des raisons de sécurité), et plus
        // facilement testable/mockable.
        try {
            $response = $this->httpClient->request('GET', $audioFilePath, ['timeout' => 60]);
            $rawContent = $response->getContent();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Impossible de télécharger le fichier audio depuis le stockage sécurisé.', $e->getCode(), previous: $e);
        }

        Assert::stringNotEmpty($rawContent, 'Le fichier audio téléchargé est vide.');

        // 3. Encodage Base64 certifié à 100% string
        $audioContent = base64_encode($rawContent);
        $isKyb = $folder instanceof BusinessFolder;
        $systemInstruction = $isKyb
            ? "Tu analyses un entretien d'une PERSONNE MORALE (Holding/Entreprise). Extrais la structure, les UBO, l'origine des fonds."
            : "Tu analyses un entretien d'une PERSONNE PHYSIQUE. Extrais la situation familiale, patrimoniale et les objectifs.";

        // State Reducer Pattern
        $existingReport = $folder->postMeetingReport;
        $previousContext = null !== $existingReport
            ? 'HISTORIQUE PRÉCÉDENT À CONSERVER ET ENRICHIR : ' . json_encode($existingReport, \JSON_UNESCAPED_UNICODE)
            : 'Ceci est la première analyse de cet entretien.';

        // 🛡️ Le prompt Grounding (Garde-fou AMF) est maintenu
        $prompt = <<<TEXT
            Tu es un expert en conformité réglementaire AMF.
            {$systemInstruction}

            RÈGLES ABSOLUES ET IMPÉRATIVES :
            1. Base-toi UNIQUEMENT sur les propos tenus dans l'audio.
            2. INTERDICTION STRICTE d'inventer des données. Ne suppose rien. Pas d'âge, pas de montant, pas de situation familiale si ce n'est pas prononcé.
            3. Si l'audio est un test, hors sujet ou vide de données financières, écris "Test ou hors sujet" et laisse les autres champs ouverts/vides.
            4. Si une info manque, indique "Non mentionné".

            {$previousContext}

            INSTRUCTIONS DE MISE À JOUR :
            1. FUSIONNE les nouvelles informations avec l'historique précédent.
            2. NE SUPPRIME AUCUN fait de l'historique sauf si le client le contredit dans le nouvel audio.

            Format STRICTEMENT JSON pur :
            {
              "executiveSummary": "Synthèse factuelle stricte. Indique si c'est un test.",
              "riskProfileDetected": "Prudent/Equilibré/Dynamique ou 'Non déterminé'",
              "kycUpdates": ["Liste uniquement les faits EXPLICITEMENT prononcés"],
              "actionPlan": ["Action à mener", "Laisse vide si rien à faire"]
            }
            TEXT;

        $url = sprintf('https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key=%s', $this->geminiApiKey);

        $response = $this->httpClient->request('POST', $url, [
            'json' => [
                'contents' => [[
                    'parts' => [
                        ['text' => $prompt],
                        ['inline_data' => ['mime_type' => 'audio/webm', 'data' => $audioContent]],
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

        return new HolisticMeetingReportDto(
            executiveSummary: $result['executiveSummary'] ?? 'Analyse impossible.',
            riskProfileDetected: $result['riskProfileDetected'] ?? 'Non déterminé',
            kycUpdates: $result['kycUpdates'] ?? [],
            actionPlan: $result['actionPlan'] ?? [],
        );
    }
}
