<?php

declare(strict_types=1);

namespace App\Application\Compliance\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateClientRequest
{
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(max: 100)]
    public string $firstName = '';

    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 100)]
    public string $lastName = '';

    #[Assert\NotBlank(message: 'L\'e-mail est obligatoire.')]
    #[Assert\Email(message: 'Adresse e-mail invalide.')]
    #[Assert\Length(max: 180)]
    public string $email = '';
}
