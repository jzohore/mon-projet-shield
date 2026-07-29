<?php

declare(strict_types=1);

namespace App\Application\ExternalAPI\Siren;

/**
 * DTO qui transporte le résultat d'une vérification SIREN.
 */
readonly class SirenResult
{
    public function __construct(
        public bool $isActive,
        public string $message,
        public string $etatAdministratif,
    ) {
    }
}
