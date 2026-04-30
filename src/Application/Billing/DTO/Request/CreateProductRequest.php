<?php

namespace App\Application\Billing\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateProductRequest
{
    public string $reference;

    #[Assert\NotBlank(message: "Le nom du pack est obligatoire")]
    #[Assert\Length(min: 3, max: 50)]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Positive(message: "Le nombre de crédits doit être supérieur à 0")]
    public int $credits;

    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    public int $priceInCents;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: "/^prod_[a-zA-Z0-9]+$/", message: "L'ID Stripe doit commencer par prod_")]
    public string $stripePriceId;

    public ?string $description = null;
    public bool $isRecommended = false;

    public bool $isRecurring;
}
