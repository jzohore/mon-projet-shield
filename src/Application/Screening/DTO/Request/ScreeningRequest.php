<?php

declare(strict_types=1);

namespace App\Application\Screening\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class ScreeningRequest
{
    #[Assert\NotBlank(message: 'Le nom de l\'entité à rechercher est obligatoire.')]
    #[Assert\Length(min: 2, minMessage: 'Le nom doit contenir au moins 2 caractères.')]
    public string $nameToSearch;

    public string $schemaToSearch;

    public string $workspaceSlugId;

    // Permet au UseCase d'être appelé par le module KYC sans double-facturer
    public bool $chargeCredit = true;

    // Ajout de l'email pour le journal d'audit interne
    public string $userEmail;
}
