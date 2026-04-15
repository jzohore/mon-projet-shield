<?php

namespace App\Domain\Billing\Exception;

class NotEnoughCreditsException extends \DomainException
{
    public function __construct(string $message = "Le solde de crédits est insuffisant pour effectuer cette action.")
    {
        parent::__construct($message);
    }
}
