<?php

declare(strict_types=1);

namespace App\Application\User\DTO\Request;

use App\Domain\User\Enum\JobRole;
use Symfony\Component\Validator\Constraints as Assert;

class UserProfilRequest
{
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[\p{L}\s\-\']+$/u',
        message: 'Le prénom contient des caractères non autorisés.'
    )]
    public ?string $firstName = null;

    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^[\p{L}\s\-\']+$/u',
        message: 'Le nom contient des caractères non autorisés.'
    )]
    public ?string $lastName = null;

    public ?JobRole $jobTitle = null;

    #[Assert\Regex(
        pattern: '/^\+?[0-9\s\-]{8,20}$/',
        message: 'Le format du numéro de téléphone est invalide (ex: +33 6 12 34 56 78).'
    )]
    #[Assert\Length(
        min: 10,
        max: 14,
        minMessage: 'Le numéro de téléphone doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le numéro de téléphone ne doit pas dépasser {{ limit }} caractères.'
    )]
    public ?string $phoneNumber = null;

    public ?string $userSlugId = null;

    public ?string $lang = null;
}
