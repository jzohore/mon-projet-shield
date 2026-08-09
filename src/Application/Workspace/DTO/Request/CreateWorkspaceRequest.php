<?php

declare(strict_types=1);

namespace App\Application\Workspace\DTO\Request;

use App\Domain\Workspace\Enum\Industry;
use Symfony\Component\Validator\Constraints as Assert;

class CreateWorkspaceRequest
{
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[\p{L}0-9\s\-\'\.,&]+$/u',
        message: 'Ce champ contient des caractères non autorisés (les symboles spéciaux comme <, >, = sont interdits).'
    )]
    public string $name = '';

    #[Assert\Regex(
        pattern: '/^\d{14}$/',
        message: 'Le SIRET doit contenir exactement 14 chiffres, sans espaces.'
    )]
    public string $siret = '';

    // 🛡️ NOUVEAU : Validation de l'adresse
    #[Assert\NotBlank(message: 'L\'adresse de la structure est obligatoire.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'L\'adresse ne peut pas dépasser {{ limit }} caractères.'
    )]
    public string $address = '';

    public string $etatAdministratif = '';

    public string $siren = '';
    public string $legalName = '';

    public Industry $workspaceIndustry = Industry::OTHER;
}
