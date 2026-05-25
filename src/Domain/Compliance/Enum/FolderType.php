<?php

namespace App\Domain\Compliance\Enum;

enum FolderType: string
{
    case INDIVIDUAL = 'individual';
    case BUSINESS = 'business';
}
