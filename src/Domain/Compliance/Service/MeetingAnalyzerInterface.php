<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Service;

use App\Application\Compliance\DTO\Request\HolisticMeetingReportDto;
use App\Domain\Compliance\Entity\ComplianceFolder;

interface MeetingAnalyzerInterface
{
    public function analyzeCompleteMeeting(ComplianceFolder $folder, string $audioFilePath): HolisticMeetingReportDto;
}
