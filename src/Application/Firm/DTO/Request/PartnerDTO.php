<?php

declare(strict_types=1);

namespace App\Application\Firm\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class PartnerDTO
{
    #[Assert\NotBlank(message: 'Le nom du partenaire est obligatoire.')]
    #[Assert\Length(max: 255)]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'L\'adresse est obligatoire.')]
    #[Assert\Length(min: 10)]
    public ?string $address = null;

    #[Assert\NotBlank(message: 'L\'email est obligatoire.')]
    #[Assert\Email(message: 'Format d\'email invalide.')]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Le téléphone est obligatoire.')]
    #[Assert\Regex(pattern: '/^\+?[0-9\s\-]{8,20}$/', message: 'Format de téléphone invalide.')]
    public ?string $phone = null;
}
