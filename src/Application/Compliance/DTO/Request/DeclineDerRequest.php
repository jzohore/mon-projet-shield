<?php

declare(strict_types=1);

namespace App\Application\Compliance\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Refus du DER par le client depuis la page publique. Le motif est facultatif.
 */
class DeclineDerRequest
{
    public string $token = '';

    #[Assert\Length(max: 2000)]
    public ?string $reason = null;
}
