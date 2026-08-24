<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceFolder;

use App\Application\Compliance\DTO\Response\HolisticMeetingReportDto;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Entity\MeetingRecording;

final readonly class BuildHolisticMeetingReportUseCase
{
    public function __invoke(ComplianceFolder $folder): HolisticMeetingReportDto
    {
        /** @var MeetingRecording[] $recordings */
        $recordings = $folder->meetingRecordings->toArray();

        // 🛡️ SÉCURITÉ : On s'assure de trier du plus ancien au plus récent (ASC)
        // pour que la chronologie de lecture soit logique pour le CGP.
        usort($recordings, static fn (MeetingRecording $a, MeetingRecording $b): int => $a->recordedAt <=> $b->recordedAt);

        $globalSummary = '';
        $globalRiskProfile = 'Non déterminé';
        $globalKycUpdates = [];
        $globalActionPlan = [];

        foreach ($recordings as $recording) {
            $output = $recording->geminiRawOutput;
            if (empty($output)) {
                continue;
            }

            // Formatage de la date (ex: 22/08/2026 à 13:07)
            // On utilise le fuseau horaire de Paris pour l'affichage UI
            $dateStr = $recording->recordedAt->setTimezone(new \DateTimeZone('Europe/Paris'))->format('d/m/Y à H:i');

            // 1. Fusion du résumé (Avec ligne de démarcation visuelle)
            if (!empty($output['executiveSummary']) && 'Test ou hors sujet' !== $output['executiveSummary']) {
                $globalSummary .= sprintf("Session du %s \n%s\n\n", $dateStr, trim($output['executiveSummary']));
            }

            // 2. Profil de risque (Le plus récent fait foi)
            if (!empty($output['riskProfileDetected']) && 'Non déterminé' !== $output['riskProfileDetected']) {
                $globalRiskProfile = $output['riskProfileDetected'];
            }

            // 🚀 3. Fusion des KYC Updates (Mode regroupement lisible)
            if (!empty($output['kycUpdates'])) {
                // array_filter retire automatiquement les chaînes vides ("") du résultat
                $sessionKycItems = array_filter(array_map(trim(...), $output['kycUpdates']));

                if ([] !== $sessionKycItems) {
                    $globalKycUpdates[] = [
                        'date' => $dateStr,
                        // array_values répare les index cassés par array_filter (ex: 0, 2, 3 -> 0, 1, 2)
                        'items' => array_values($sessionKycItems),
                    ];
                }
            }

            // 🚀 4. Action Plan : Création d'un bloc structuré
            if (!empty($output['actionPlan'])) {
                $sessionActionItems = array_filter(array_map(trim(...), $output['actionPlan']));

                if ([] !== $sessionActionItems) {
                    $globalActionPlan[] = [
                        'date' => $dateStr,
                        'items' => array_values($sessionActionItems),
                    ];
                }
            }
        }

        return new HolisticMeetingReportDto(
            executiveSummary: trim($globalSummary) ?: 'Aucune donnée exploitable extraite des sessions.',
            riskProfileDetected: $globalRiskProfile,
            kycUpdates: $globalKycUpdates,
            actionPlan: $globalActionPlan
        );
    }
}
