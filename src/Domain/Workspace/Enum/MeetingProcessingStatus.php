<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Enum;

enum MeetingProcessingStatus: string
{
    case FINALIZING = 'finalizing'; // recollage des chunks S3 en cours
    case ANALYZING = 'analyzing';   // IA en train de rédiger la synthèse
    case DONE = 'done';             // rapport disponible
}
