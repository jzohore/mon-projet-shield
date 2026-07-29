<?php

declare(strict_types=1);

namespace App\Application\User\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateUserRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'L\'email est obligatoire.')]
        #[Assert\Email(message: 'Adresse email invalide.')]
        public string $email = '',

        #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
        public string $firstName = '',

        #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
        public string $lastName = '',
    ) {
    }
}
