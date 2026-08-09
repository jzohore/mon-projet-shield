<?php

declare(strict_types=1);

namespace App\Domain\Notifer;

interface NotifierInterface
{
    public function send(string $texteMessage): void;
}
