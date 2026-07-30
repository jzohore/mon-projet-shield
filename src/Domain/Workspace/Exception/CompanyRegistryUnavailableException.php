<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Exception;

final class CompanyRegistryUnavailableException extends \RuntimeException
{
    /**
     * Surcharge du constructeur pour forcer un message par défaut clair,
     * tout en permettant de passer l'exception technique initiale (Previous)
     * pour que Sentry/Monolog puisse tracer le vrai crash réseau.
     */
    public function __construct(
        string $message = 'Le registre officiel des entreprises est temporairement injoignable.',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
