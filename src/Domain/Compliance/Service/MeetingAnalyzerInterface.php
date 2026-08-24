<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Service;

use App\Domain\Compliance\Entity\MeetingRecording;

interface MeetingAnalyzerInterface
{
    public function analyzeCompleteMeeting(MeetingRecording $recording): void;
}
