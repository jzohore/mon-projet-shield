<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

use App\Domain\User\Enum\JobRole;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class UserProfil
{
    /**
     * @var bool dismiss the onboarding steps
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public private(set) bool $dismissOnboarding = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public private(set) ?string $stripeCustomerId = null;

    #[ORM\Column(type: Types::STRING, length: 5, nullable: true)]
    public private(set) ?string $lang = null;

    public function __construct(
        #[ORM\Column(type: Types::ENUM, length: 100, nullable: true, enumType: JobRole::class)]
        public ?JobRole $jobTitle = null,
        #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
        public ?string $phoneNumber = null,
    ) {
    }

    public function isDismiss(): bool
    {
        return $this->dismissOnboarding;
    }

    public function setDismissOnboarding(bool $dismissOnboarding): void
    {
        $this->dismissOnboarding = $dismissOnboarding;
    }

    public function updateStripeCustomerId(string $stripeCustomerId): void
    {
        $this->stripeCustomerId = $stripeCustomerId;
    }
}
