<?php

namespace App\Domain\Product\Entity;

use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: '`products`')]
class Product
{
    use GenerateSlugPrefixedTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null {
        get => $this->id;
    }

    #[ORM\Column(type: Types::STRING, length: 255)]
    public private(set) string $name;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public private(set) string $slugId;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public private(set) ?string $description = null;

    /**
     * Le nombre de crédits injectés sur le compte après paiement.
     */
    #[ORM\Column(type: Types::INTEGER)]
    public private(set) int $credits;

    /**
     * Prix en centimes (ex: 18000 pour 180,00 €). Toujours utiliser des centimes avec Stripe.
     */
    #[ORM\Column(type: Types::INTEGER)]
    public private(set) int $priceInCents;

    /**
     * L'identifiant du tarif côté Stripe (ex: price_1PQabc123).
     */
    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public private(set) string $stripePriceId;

    /**
     * Permet de mettre en avant un produit précis sur le front-end (ex: Le Pack Pro).
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public private(set) bool $isRecommended = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $updatedAt = null;

    public function __construct(
        string $name,
        int $credits,
        int $priceInCents,
        string $stripePriceId,
        ?string $description = null,
        bool $isRecommended = false,
    ) {
        $this->name = $name;
        $this->credits = $credits;
        $this->priceInCents = $priceInCents;
        $this->stripePriceId = $stripePriceId;
        $this->description = $description;
        $this->isRecommended = $isRecommended;
        $this->slugId = $this->generate_ulid_prefixed('product_');

        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public static function create(
        string $name,
        int $credits,
        int $priceInCents,
        string $stripePriceId,
        ?string $description = null,
        bool $isRecommended = false,
    ): self {
        return new self(
            $name,
            $credits,
            $priceInCents,
            $stripePriceId,
            $description,
            $isRecommended
        );
    }

    public function getFormattedPrice(): string
    {
        // Retourne "180.00" (facile à afficher dans Twig)
        return number_format($this->priceInCents / 100, 2, ',', ' ');
    }

    public function updatePrice(int $newPriceInCents, string $newStripePriceId): void
    {
        $this->priceInCents = $newPriceInCents;
        $this->stripePriceId = $newStripePriceId;
        $this->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function toggleRecommendation(): void
    {
        $this->isRecommended = !$this->isRecommended;
        $this->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
