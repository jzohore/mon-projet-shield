<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Exception;

/**
 * Exception levée lorsqu'un portefeuille n'a pas le solde requis
 * pour effectuer une opération de débit.
 */
final class InsufficientCreditsException extends \DomainException
{
    // On peut définir un message par défaut clair pour ne pas avoir à le réécrire partout
    public function __construct(
        string $message = 'Solde de crédits insuffisant pour réaliser cette opération. Veuillez recharger votre compte.',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
