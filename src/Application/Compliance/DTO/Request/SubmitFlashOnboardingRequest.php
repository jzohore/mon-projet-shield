<?php

declare(strict_types=1);

namespace App\Application\Compliance\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class SubmitFlashOnboardingRequest
{
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le prénom ne peut pas dépasser 100 caractères.')]
    public string $firstName = '';

    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le nom ne peut pas dépasser 100 caractères.')]
    public string $lastName = '';

    #[Assert\Email(message: 'L\'adresse email n\'est pas valide.')]
    #[Assert\NotBlank(message: 'L\'adresse email est obligatoire.')]
    public string $email = '';

    public string $workspaceUuid = '';
}
