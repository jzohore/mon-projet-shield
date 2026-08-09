<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

enum UserType: string
{
    case CGP = 'cgp';
    case CLIENT = 'client';
    case ADMIN = 'admin';
}
