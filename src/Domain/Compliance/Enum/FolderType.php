<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Enum;

enum FolderType: string
{
    case INDIVIDUAL = 'individual';
    case BUSINESS = 'business';
}
