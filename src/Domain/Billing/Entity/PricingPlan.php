<?php

declare(strict_types=1);

namespace App\Domain\Billing\Entity;

use App\Domain\Billing\Enum\PricingPlanKind;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;

use function Symfony\Component\Clock\now;

use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

/**
 * Offre commerciale KYSURE persistée : prix et identifiants Stripe stockés en
 * base pour survivre à un reset de la base (re-provisionnés par
 * `app:billing:sync-pricing`). Lue par le résolveur de prix et la page pricing.
 */
#[ORM\Entity]
#[ORM\Table(name: 'pricing_plans')]
class PricingPlan
{
    use GenerateSlugPrefixedTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public private(set) ?Uuid $id = null;

    #[ORM\Column(type: Types::STRING, length: 64, unique: true)]
    public private(set) string $slugId;

    /** Clé stable, ex. individual_seat, cabinet_seat, minutes_300. */
    #[ORM\Column(type: Types::STRING, length: 64, unique: true)]
    public private(set) string $planKey;

    /** Prix unitaire en centimes (par siège pour un abonnement, total pour un pack). */
    #[ORM\Column(type: Types::INTEGER)]
    public private(set) int $unitAmountCents;

    /** Minutes d'entretien : incluses par siège (abonnement) ou créditées (pack). */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    public private(set) int $meetingMinutes = 0;

    /** Sièges minimum facturables (abonnement). */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1])]
    public private(set) int $minSeats = 1;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public private(set) ?string $stripeProductId = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true, nullable: true)]
    public private(set) ?string $stripePriceId = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public private(set) bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $updatedAt = null;

    private function __construct(
        string $planKey,
        #[ORM\Column(type: Types::STRING, length: 30, enumType: PricingPlanKind::class)]
        public private(set) PricingPlanKind $kind,
        #[ORM\Column(type: Types::STRING, length: 120)]
        public private(set) string $label,
        int $unitAmountCents,
        int $meetingMinutes,
        int $minSeats,
    ) {
        Assert::stringNotEmpty($planKey);
        Assert::greaterThanEq($unitAmountCents, 0);
        Assert::greaterThanEq($meetingMinutes, 0);
        Assert::greaterThanEq($minSeats, 1);

        $this->planKey = $planKey;
        $this->unitAmountCents = $unitAmountCents;
        $this->meetingMinutes = $meetingMinutes;
        $this->minSeats = $minSeats;
        $this->slugId = $this->generate_ulid_prefixed('price_');
        $this->createdAt = now();
    }

    public static function create(
        string $planKey,
        PricingPlanKind $kind,
        string $label,
        int $unitAmountCents,
        int $meetingMinutes = 0,
        int $minSeats = 1,
    ): self {
        return new self($planKey, $kind, $label, $unitAmountCents, $meetingMinutes, $minSeats);
    }

    /** Rattache les identifiants Stripe créés lors de la synchronisation. */
    public function linkStripe(string $stripeProductId, string $stripePriceId): void
    {
        $this->stripeProductId = $stripeProductId;
        $this->stripePriceId = $stripePriceId;
        $this->updatedAt = now();
    }

    public function updatePricing(int $unitAmountCents, int $meetingMinutes, int $minSeats, string $label): void
    {
        $this->unitAmountCents = $unitAmountCents;
        $this->meetingMinutes = $meetingMinutes;
        $this->minSeats = $minSeats;
        $this->label = $label;
        $this->updatedAt = now();
    }

    public function isProvisioned(): bool
    {
        return null !== $this->stripePriceId && '' !== $this->stripePriceId;
    }

    public function formattedPrice(): string
    {
        return number_format($this->unitAmountCents / 100, 2, ',', ' ');
    }
}
