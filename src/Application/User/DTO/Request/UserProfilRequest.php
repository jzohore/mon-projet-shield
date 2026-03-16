<?php

declare(strict_types=1);

namespace App\Application\User\DTO\Request;

use App\Domain\User\Enum\JobRole;
use Symfony\Component\Validator\Constraints as Assert;

class UserProfilRequest
{
    #[Assert\NotBlank(message: 'Veuillez sélectionner votre rôle.')]
    public ?JobRole $jobTitle = null;

    #[Assert\NotBlank(message: 'Le numéro de téléphone est obligatoire pour la sécurité de votre compte.')]
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
