<?php

declare(strict_types=1);

namespace App\Application\Portal\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Informations que le client final peut modifier lui-même depuis son espace :
 * son identité d'affichage et son téléphone. L'e-mail (identifiant de connexion
 * par lien magique) n'est volontairement pas modifiable ici.
 */
class UpdateClientProfileRequest
{
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères.')]
    #[Assert\Regex(
        pattern: "/^[\p{L}\s\-']+$/u",
        message: 'Le prénom contient des caractères non autorisés.'
    )]
    public string $firstName = '';

    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
    #[Assert\Regex(
        pattern: "/^[\p{L}\s\-']+$/u",
        message: 'Le nom contient des caractères non autorisés.'
    )]
    public string $lastName = '';

    #[Assert\Length(max: 20, maxMessage: 'Le téléphone ne peut pas dépasser {{ limit }} caractères.')]
    #[Assert\Regex(
        pattern: '/^[0-9\s\+\.\-\(\)]{6,20}$/',
        message: 'Numéro de téléphone invalide.',
    )]
    public ?string $phoneNumber = null;
}
