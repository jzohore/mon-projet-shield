<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceFolder;

use App\Application\Compliance\DTO\Response\HolisticMeetingReportDto;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Entity\MeetingRecording;
use App\Domain\Compliance\Repository\MeetingRecordRepositoryInterface;

/**
 * Reconstruit à la volée la synthèse d'entretien consolidée d'un dossier, en
 * fusionnant dans l'ordre chronologique la sortie brute de l'IA de chaque
 * enregistrement actif.
 *
 * C'est la source de vérité du BROUILLON. Le DTO produit ici est, une fois
 * validé par le CGP, figé tel quel dans
 * {@see \App\Domain\Compliance\Entity\ValidatedMeetingReport} : toute évolution
 * de sa forme est donc un sujet de compatibilité pour les rapports déjà validés.
 */
final readonly class BuildHolisticMeetingReportUseCase
{
    /** Valeur renvoyée par l'IA quand elle n'a pas su qualifier le risque. */
    private const string RISK_PROFILE_UNKNOWN = 'Non déterminé';

    /** Marqueur renvoyé par l'IA pour un enregistrement de test / hors sujet. */
    private const string OFF_TOPIC_MARKER = 'Test ou hors sujet';

    /** Fuseau d'affichage des dates de session (lecture CGP). */
    private const string DISPLAY_TIMEZONE = 'Europe/Paris';

    private const string EMPTY_STATE_MESSAGE = "L'analyse IA n'a détecté aucune donnée exploitable dans les enregistrements de cet entretien (audio inaudible ou hors sujet). Vous pouvez relancer une nouvelle capture vocale.";

    public function __construct(
        private MeetingRecordRepositoryInterface $meetingRecordRepository,
    ) {
    }

    public function __invoke(ComplianceFolder $folder): HolisticMeetingReportDto
    {
        $recordings = $this->chronologicalActiveRecordings($folder);
        $displayTimezone = new \DateTimeZone(self::DISPLAY_TIMEZONE);

        $summaryBlocks = [];
        $riskProfile = self::RISK_PROFILE_UNKNOWN;
        $kycUpdates = [];
        $actionPlan = [];
        $sourceSlugIds = [];

        foreach ($recordings as $recording) {
            $output = $recording->geminiRawOutput;

            if (null === $output || [] === $output) {
                continue;
            }

            $sourceSlugIds[] = $recording->slugId;
            $sessionDate = $recording->recordedAt->setTimezone($displayTimezone)->format('d/m/Y à H:i');

            // 1. Synthèse : un bloc par session exploitable.
            $sessionSummary = trim($output['executiveSummary'] ?? '');
            if ('' !== $sessionSummary && self::OFF_TOPIC_MARKER !== $sessionSummary) {
                $summaryBlocks[] = sprintf("Session du %s\n%s", $sessionDate, $sessionSummary);
            }

            // 2. Profil de risque : le plus récent (dernier itéré) fait foi.
            $sessionRisk = trim($output['riskProfileDetected'] ?? '');
            if ('' !== $sessionRisk && self::RISK_PROFILE_UNKNOWN !== $sessionRisk) {
                $riskProfile = $sessionRisk;
            }

            // 3. + 4. KYC / plan d'action : un groupe daté par session non vide.
            $sessionKyc = $this->normalizeItems($output['kycUpdates'] ?? null);
            if ([] !== $sessionKyc) {
                $kycUpdates[] = ['date' => $sessionDate, 'items' => $sessionKyc];
            }

            $sessionActions = $this->normalizeItems($output['actionPlan'] ?? null);
            if ([] !== $sessionActions) {
                $actionPlan[] = ['date' => $sessionDate, 'items' => $sessionActions];
            }
        }

        $summary = implode("\n\n", $summaryBlocks);
        $isExplorable = '' !== $summary;

        return new HolisticMeetingReportDto(
            executiveSummary: $isExplorable ? $summary : self::EMPTY_STATE_MESSAGE,
            riskProfileDetected: $riskProfile,
            kycUpdates: $kycUpdates,
            actionPlan: $actionPlan,
            slugId: $sourceSlugIds,
            isExplorable: $isExplorable,
        );
    }

    /**
     * @return MeetingRecording[] du plus ancien au plus récent
     */
    private function chronologicalActiveRecordings(ComplianceFolder $folder): array
    {
        $recordings = $this->meetingRecordRepository->findActiveByFolder($folder);

        // Le repository trie déjà en ASC ; on le garantit ici pour que la fusion
        // ne dépende pas de ce détail d'implémentation.
        usort(
            $recordings,
            static fn (MeetingRecording $a, MeetingRecording $b): int => $a->recordedAt <=> $b->recordedAt,
        );

        return $recordings;
    }

    /**
     * Nettoie une liste d'items renvoyée par l'IA : chaînes trimmées, vides
     * écartées, index recompacté. Tolérant à une clé absente.
     *
     * @param array<int, string>|null $rawItems
     *
     * @return list<string>
     */
    private function normalizeItems(?array $rawItems): array
    {
        if (null === $rawItems) {
            return [];
        }

        $items = array_filter(array_map(trim(...), $rawItems));

        return array_values($items);
    }
}
