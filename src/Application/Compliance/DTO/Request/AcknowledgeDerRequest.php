<?php

declare(strict_types=1);

namespace App\Application\Compliance\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Saisie du client sur la page d'accusé de réception du DER.
 *
 * `token`, `ipAddress` et `userAgent` sont renseignés par le contrôleur, jamais
 * par le formulaire.
 */
class AcknowledgeDerRequest
{
    public string $token = '';

    #[Assert\NotBlank(message: 'Merci d\'indiquer votre nom et prénom.')]
    #[Assert\Length(max: 255)]
    public string $declaredName = '';

    #[Assert\IsTrue(message: 'Vous devez confirmer avoir reçu et pris connaissance du document.')]
    public bool $accepted = false;

    public ?string $ipAddress = null;

    public ?string $userAgent = null;
}
