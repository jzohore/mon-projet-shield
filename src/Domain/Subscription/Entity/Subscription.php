<?php

namespace App\Domain\Subscription\Entity;

use App\Domain\Subscription\Enum\Plan;
use App\Domain\Workspace\Entity\Workspace;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'subscriptions')]
class Subscription
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null {
        get => $this->id;
    }

    // 💡 IMMUTABILITÉ : Un abonnement appartient à un cabinet à vie.
    #[ORM\OneToOne(targetEntity: Workspace::class, inversedBy: 'subscription')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public readonly Workspace $workspace;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: Plan::class)]
    public Plan $plan {
        get => $this->plan;
        set => $this->plan = $value;
    }

    // 💡 UPSELL : Nombre de sièges achetés en plus du forfait de base
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    public int $extraSeats = 0 {
        get => $this->extraSeats;
        set {
            if ($value < 0) {
                throw new \InvalidArgumentException("Le nombre de sièges supplémentaires ne peut pas être négatif.");
            }
            $this->extraSeats = $value;
        }
    }

    // Date de fin (null = abonnement à vie ou facturation gérée 100% par Stripe)
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $expiresAt = null {
        get => $this->expiresAt;
        set => $this->expiresAt = $value;
    }

    // Permet de couper l'accès manuellement (ex: impayé Stripe)
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public bool $isActive = true {
        get => $this->isActive;
        set => $this->isActive = $value;
    }

    // ID de référence chez ton prestataire de paiement
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public ?string $stripeSubscriptionId = null {
        get => $this->stripeSubscriptionId;
        set => $this->stripeSubscriptionId = $value;
    }

    public function __construct(Workspace $workspace, Plan $plan, ?\DateTimeImmutable $expiresAt = null)
    {
        $this->workspace = $workspace;
        $this->plan = $plan;
        $this->expiresAt = $expiresAt;
    }

    public static function create(Workspace $workspace, Plan $plan, ?\DateTimeImmutable $expiresAt = null): self
    {
        return new self($workspace, $plan, $expiresAt);
    }

    // --- MÉTHODES MÉTIER (DDD) ---

    /**
     * Calcule le quota maximum de membres pour ce cabinet.
     */
    public function getMaxSeats(): int
    {
        return $this->plan->getIncludedSeats() + $this->extraSeats;
    }

    /**
     * Vérifie si l'abonnement permet toujours d'accéder à l'application.
     */
    public function isValid(): bool
    {
        if (!$this->isActive) {
            return false;
        }

        if ($this->expiresAt !== null && $this->expiresAt < new \DateTimeImmutable('now', new \DateTimeZone('UTC'))) {
            return false;
        }

        return true;
    }
}
