<?php

declare(strict_types=1);

namespace App\Application\Compliance\DTO\Response;

final readonly class HolisticMeetingReportDto
{
    /**
     * @param array<int, array{date: string, items: array<int, string>}> $kycUpdates
     * @param array<int, array{date: string, items: array<int, string>}> $actionPlan
     */
    public function __construct(
        public string $executiveSummary,
        public string $riskProfileDetected,
        public array $kycUpdates,
        public array $actionPlan,
    ) {
    }

    /**
     * @return array{
     *     summary: string,
     *     riskProfile: string,
     *     kycUpdates: array<int, array{date: string, items: array<int, string>}>,
     *     actionPlan: array<int, array{date: string, items: array<int, string>}>
     * }
     */
    public function toArray(): array
    {
        return [
            'summary' => $this->executiveSummary,
            'riskProfile' => $this->riskProfileDetected,
            'kycUpdates' => $this->kycUpdates,
            'actionPlan' => $this->actionPlan,
        ];
    }
}
