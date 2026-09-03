<?php

declare(strict_types=1);

namespace App\Domain\Compliance\ValueObject;

use App\Domain\Compliance\Enum\AdvisoryRiskProfile;

/**
 * Corrections apportées par le CGP au brouillon de synthèse d'entretien avant
 * de le figer. Un champ à `null` = « non modifié, garder la valeur de l'IA ».
 */
final readonly class MeetingReportAdjustments
{
    private function __construct(
        public ?string $summary = null,
        public ?AdvisoryRiskProfile $riskProfile = null,
    ) {
    }

    public static function none(): self
    {
        return new self();
    }

    public static function fromInput(?string $summary, ?AdvisoryRiskProfile $riskProfile): self
    {
        return new self(self::normalize($summary), $riskProfile);
    }

    public function isEmpty(): bool
    {
        return null === $this->summary && !$this->riskProfile instanceof AdvisoryRiskProfile;
    }

    /**
     * Applique les corrections sur la copie figée du rapport et marque
     * l'instantané comme amendé par le CGP.
     *
     * @param array{
     *     summary: string,
     *     riskProfile: string,
     *     kycUpdates: array<int, array{date: string, items: array<int, string>}>,
     *     actionPlan: array<int, array{date: string, items: array<int, string>}>,
     *     slugId: array<int, string>,
     *     isExplorable?: bool,
     *     isAdjusted?: bool
     * } $content
     *
     * @return array{
     *     summary: string,
     *     riskProfile: string,
     *     kycUpdates: array<int, array{date: string, items: array<int, string>}>,
     *     actionPlan: array<int, array{date: string, items: array<int, string>}>,
     *     slugId: array<int, string>,
     *     isExplorable?: bool,
     *     isAdjusted?: bool
     * }
     */
    public function applyTo(array $content): array
    {
        if (null !== $this->summary) {
            $content['summary'] = $this->summary;
        }

        if ($this->riskProfile instanceof AdvisoryRiskProfile) {
            $content['riskProfile'] = $this->riskProfile->value;
        }

        if (!$this->isEmpty()) {
            $content['isAdjusted'] = true;
        }

        return $content;
    }

    private static function normalize(?string $value): ?string
    {
        $value = null !== $value ? trim($value) : '';

        return '' !== $value ? $value : null;
    }
}
