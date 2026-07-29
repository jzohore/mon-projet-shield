<?php

declare(strict_types=1);

namespace App\Application\Compliance\DTO\Request;

class HolisticMeetingReportDto
{
    /**
     * @param array<string, mixed>             $kycUpdates
     * @param array<int, array<string, mixed>> $actionPlan
     */
    public function __construct(
        public string $executiveSummary,
        public string $riskProfileDetected,
        public array $kycUpdates,
        public array $actionPlan,
    ) {
    }
}
