<?php

declare(strict_types=1);

namespace App\Application\Kyc\DTO\Request;

use App\Domain\Kyc\Enum\StakeholderRole;
use Symfony\Component\Validator\Constraints as Assert;

class AddStakeholderRequest
{
    #[Assert\NotBlank(message: 'Le prénom est requis.')]
    #[Assert\Length(max: 100)]
    public ?string $firstName = null;

    #[Assert\NotBlank(message: 'Le nom est requis.')]
    #[Assert\Length(max: 100)]
    public ?string $lastName = null;

    #[Assert\NotNull(message: 'Le rôle est obligatoire.')]
    public ?StakeholderRole $role = StakeholderRole::DIRECTOR;

    #[Assert\Range(min: 0, max: 100)]
    public ?float $percentage = 25;

    public ?string $folderSlugId = null;
}
