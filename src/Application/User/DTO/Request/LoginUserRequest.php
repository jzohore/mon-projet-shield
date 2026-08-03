<?php

declare(strict_types=1);

namespace App\Application\User\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

readonly class LoginUserRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'L\'adresse email est obligatoire.')]
        #[Assert\Email(
            message: 'Le format de l\'adresse email est invalide.',
            mode: 'html5' // Le mode le plus strict et standard pour valider un email
        )]
        #[Assert\Length(
            max: 180,
            maxMessage: 'L\'email ne peut pas dépasser {{ limit }} caractères.'
        )]
        public string $email = '',
    ) {
    }
}
