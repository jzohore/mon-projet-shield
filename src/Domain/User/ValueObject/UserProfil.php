<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

use App\Domain\User\Enum\JobRole;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class UserProfil
{
    #[ORM\Column(type: Types::ENUM, length: 100, nullable: true, enumType: JobRole::class)]
    public ?JobRole $jobTitle = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    public ?string $phoneNumber = null;

    public function __construct(?JobRole $jobTitle = null, ?string $phoneNumber = null)
    {
        $this->jobTitle = $jobTitle;
        $this->phoneNumber = $phoneNumber;
    }
}
