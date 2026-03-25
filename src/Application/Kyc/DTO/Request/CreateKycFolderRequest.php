<?php

namespace App\Application\Kyc\DTO\Request;

use App\Domain\Kyc\Validator\UniqueKycAwaitingClient;
use Symfony\Component\Validator\Constraints as Assert;

class CreateKycFolderRequest
{
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères.'
    )]
    public ?string $contactFirstName = null;

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
    public ?string $contactLastName = null;

    #[Assert\NotBlank(message: 'L\'adresse email est obligatoire.')]
    #[Assert\Email(
        message: 'Le format de l\'adresse email est invalide.',
        mode: 'html5' // Le mode le plus strict et standard pour valider un email
    )]
    #[Assert\Length(
        max: 180,
        maxMessage: 'L\'email ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[UniqueKycAwaitingClient]
    public ?string $contactEmail = null;

    public ?string $workspaceSlugId = null;
}
