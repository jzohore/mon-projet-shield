<?php

namespace App\Domain\Billing\Entity;

use App\Domain\Billing\Enum\SubscriptionStatus;
use App\Domain\Workspace\Entity\Workspace;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

use function Symfony\Component\Clock\now;

#[ORM\Entity]
#[ORM\Table(name: 'subscriptions')]
class Subscription
{
    public const int PLAN_MAX_USERS = 5;
    public const int PLAN_MAX_SEARCHES_PER_MONTH = 500;
    public const int PLAN_MAX_MONITORING = 500;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null {
        get => $this->id;
    }

    // Lien avec l'espace de travail
    #[ORM\OneToOne(targetEntity: Workspace::class, inversedBy: 'subscription')]
    #[ORM\JoinColumn(nullable: false)]
    public private(set) Workspace $workspace;

    // Le statut (lié à notre Enum)
    #[ORM\Column(type: Types::STRING, enumType: SubscriptionStatus::class)]
    public private(set) SubscriptionStatus $status;

    // Le nom de ton offre interne (ex: "firm_premium", "solo_standard")
    #[ORM\Column(type: Types::STRING)]
    public private(set) string $planReference;

    // ==========================================
    // DONNÉES STRIPE (Synchronisées via Webhooks)
    // ==========================================

    // L'ID unique de l'abonnement chez Stripe (ex: sub_1MowQ...)
    #[ORM\Column(type: Types::STRING, unique: true, nullable: true)]
    public private(set) ?string $stripeSubscriptionId = null;

    // L'ID du prix payé (ex: price_1MowQ...)
    #[ORM\Column(type: Types::STRING)]
    public private(set) string $stripePriceId;

    // Début de la période de facturation en cours
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $currentPeriodStart;

    // Fin de la période de facturation (Très important pour couper l'accès !)
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $currentPeriodEnd;

    // Vrai si le client a cliqué sur "Annuler" mais que le mois n'est pas fini
    #[ORM\Column(type: Types::BOOLEAN)]
    public private(set) bool $cancelAtPeriodEnd = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) \DateTimeImmutable $updateAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $trialEndsAt = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public private(set) ?string $reason = null;

    /**
     * 1. On passe le constructeur en PRIVATE.
     * Seule la classe elle-même a le droit de s'instancier.
     * @throws Exception
     */
    private function __construct(
        Workspace $workspace,
        string $stripeSubscriptionId,
        string $stripePriceId,
        string $planReference,
        SubscriptionStatus $status
    ) {
        $this->workspace = $workspace;
        $this->stripeSubscriptionId = $stripeSubscriptionId;
        $this->stripePriceId = $stripePriceId;
        $this->planReference = $planReference;
        $this->status = $status;
        $this->createdAt = now();
        $this->updateAt = now();
    }

    /**
     * 2. Le Named Constructor (La Factory statique)
     */
    public static function create(
        Workspace $workspace,
        string $stripeSubscriptionId,
        string $stripePriceId,
        string $planReference,
        SubscriptionStatus $status = SubscriptionStatus::INCOMPLETE
    ): self {
        $subscription = new self(
            $workspace,
            $stripeSubscriptionId,
            $stripePriceId,
            $planReference,
            $status
        );

        // Initialisation des dates par défaut lors de la création
        $subscription->currentPeriodStart = new \DateTimeImmutable();

        // On donne 1h de battement le temps que le Webhook Stripe confirme le 1er paiement
        $subscription->currentPeriodEnd = (new \DateTimeImmutable())->modify('+1 hour');
        $subscription->cancelAtPeriodEnd = false;

        return $subscription;
    }

    public static function startCabinetTrial(
        Workspace $workspace,
        string $stripeSubscriptionId,
        string $stripePriceId
    ): self {
        $now = new \DateTimeImmutable();
        $trialEnd = $now->modify('+30 days');

        // On appelle le constructeur privé
        $subscription = new self(
            $workspace,
            $stripeSubscriptionId,
            $stripePriceId,
            'kysure_cabinet_300', // Le nom de ton offre hardcodé
            SubscriptionStatus::TRIALING
        );

        // On initialise les dates spécifiques au trial
        $subscription->currentPeriodStart = $now;
        $subscription->currentPeriodEnd = $trialEnd;
        $subscription->trialEndsAt = $trialEnd;
        $subscription->cancelAtPeriodEnd = false;

        return $subscription;
    }


    /**
     * Méthode appelée UNIQUEMENT par ton contrôleur Webhook Stripe
     */
    public function syncWithStripe(
        SubscriptionStatus $status,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
        bool $cancelAtPeriodEnd
    ): void {
        $this->status = $status;
        $this->currentPeriodStart = $periodStart;
        $this->currentPeriodEnd = $periodEnd;
        $this->cancelAtPeriodEnd = $cancelAtPeriodEnd;
    }

    /**
     * Vérifie si le cabinet a le droit d'utiliser Kysure aujourd'hui
     */
    public function isValid(): bool
    {
        // 1. Le statut doit être Actif ou Trial
        if (!$this->status->isActive()) {
            return false;
        }

        // 2. La date de fin ne doit pas être dépassée
        // (On ajoute 24h de grâce en cas de retard de webhook Stripe)
        $gracePeriod = $this->currentPeriodEnd->modify('+1 day');
        if (new \DateTimeImmutable() > $gracePeriod) {
            return false;
        }

        return true;
    }

    /**
     * Retourne le nombre de jours restants pour l'essai gratuit.
     * Renvoie 0 si l'essai est terminé ou inactif.
     */
    public function getRemainingTrialDays(): int
    {
        // Si on n'est pas en période d'essai ou que la date n'est pas définie
        if ($this->status !== SubscriptionStatus::TRIALING || $this->trialEndsAt === null) {
            return 0;
        }

        $now = new \DateTimeImmutable();

        // Si la date de fin est déjà passée
        if ($now > $this->trialEndsAt) {
            return 0;
        }

        // On calcule la différence en jours
        return (int) $now->diff($this->trialEndsAt)->days;
    }

    public function activateSubscription(string $stripeSubscriptionId): void
    {
        $this->stripeSubscriptionId = $stripeSubscriptionId;
        $this->status = SubscriptionStatus::ACTIVE;
        $now = new \DateTimeImmutable();
        $end = $now->modify('+1 month');
        $this->currentPeriodStart = $now;
        $this->currentPeriodEnd = $end;
        $this->trialEndsAt = null;
    }

    public function markAsPendingCancellation(string $reason): void
    {
        $this->cancelAtPeriodEnd = true;
        $this->reason = $reason;
    }

    public function markAsTerminate(): void
    {
        $this->cancelAtPeriodEnd = false;
        $this->status = SubscriptionStatus::CANCELED;
        $this->stripeSubscriptionId = null;
    }

    public function syncSubscription(\DateTimeImmutable $currentPeriodEnd, SubscriptionStatus $status, bool $cancelPeriodEnd): void
    {
        $this->currentPeriodEnd = $currentPeriodEnd;
        $this->status = $status;
        $this->cancelAtPeriodEnd = $cancelPeriodEnd;
    }
}
