<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Exception;

/**
 * Levée quand le registre ORIAS n'a pas pu être consulté (réseau, 5xx, page
 * illisible). À distinguer d'un « non inscrit » : le statut de conformité du
 * cabinet reste inchangé et la vérification doit être réessayée.
 */
final class OriasRegistryUnavailableException extends \RuntimeException
{
    public function __construct(
        string $message = 'Le registre ORIAS est temporairement injoignable.',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
