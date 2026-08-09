<?php

declare(strict_types=1);

namespace App\Application\Firm\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateRegulatoryProfileRequest
{
    // Le numéro ORIAS : 10 chiffres (immatriculation)
    #[Assert\Regex(
        pattern: '/^\d{8}$/',
        message: 'Le numéro ORIAS doit comporter exactement 8 chiffres.'
    )]
    public ?string $oriasNumber = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    public ?string $professionalAssociation = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $rcProInsurer = null;

    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^[A-Z0-9\-\s]{5,50}$/i',
        message: 'Format de numéro de police RC Pro invalide.'
    )]
    public ?string $rcProPolicyNumber = null;

    #[Assert\NotNull]
    public bool $isIndependent = true;

    /**
     * @var PartnerDTO[]
     */
    #[Assert\Valid] // 🪄 C'est tout ce dont tu as besoin !
    public array $partners = [];
}
