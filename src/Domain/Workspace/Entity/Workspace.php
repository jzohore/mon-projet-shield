<?php

namespace App\Domain\Workspace\Entity;

use App\Domain\Subscription\Entity\Subscription;
use App\Domain\Wallet\Entity\WalletTransaction;
use App\Domain\Wallet\Exception\InsufficientCreditsException;
use App\Domain\Workspace\Enum\Industry;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'workspaces')]
class Workspace
{
    use GenerateSlugPrefixedTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null {
        get => $this->id;
    }

    // --- INFORMATIONS DE BASE ---

    // Le nom usuel/commercial du cabinet
    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public ?string $name = null {
        get => $this->name;
        set => $this->name = trim($value ?? '');
    }

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public ?string $slugId = null {
        get => $this->slugId;
    }

    // --- DONNÉES REGTECH & FACTURATION ---

    #[ORM\Column(type: Types::STRING, length: 14, unique: true, nullable: true)]
    public ?string $siret = null {
        get => $this->siret;
        set => $this->siret = $value ? trim($value) : null;
    }

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public ?string $legalName = null {
        get => $this->legalName;
        set => $this->legalName = $value ? trim($value) : null;
    }

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $address = null {
        get => $this->address;
    }

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public ?string $logoFilename = null {
        get => $this->logoFilename;
    }

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, enumType: Industry::class)]
    public ?Industry $industry = null {
        get => $this->industry;
    }

    // --- RELATIONS ---

    #[ORM\OneToOne(targetEntity: Subscription::class, mappedBy: 'workspace', cascade: ['persist', 'remove'])]
    public ?Subscription $subscription = null {
        get => $this->subscription;
        set {
            $this->subscription = $value;
            // Garantir l'intégrité de la relation bilatérale
            if ($value !== null && $value->workspace !== $this) {
                // $value->workspace = $this; // Sera fait via le constructeur de Subscription
            }
        }
    }

    /**
     * @var Collection<int, WorkspaceMember>
     */
    #[ORM\OneToMany(targetEntity: WorkspaceMember::class, mappedBy: 'workspace', cascade: ['persist', 'remove'])]
    public Collection $members {
        get => $this->members;
    }

    /**
     * @var Collection<int, WorkspaceInvitation>
     */
    #[ORM\OneToMany(targetEntity: WorkspaceInvitation::class, mappedBy: 'workspace', cascade: ['persist', 'remove'])]
    public Collection $invitations {
        get => $this->invitations;
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt {
        get => $this->createdAt;
    }

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public private(set) int $balance = 0;

    /**
     * @var array<int|string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    public private(set) ?array $transactions = null;

    /**
     * @var Collection<int, WalletTransaction>
     */
    #[ORM\OneToMany(targetEntity: WalletTransaction::class, mappedBy: 'workspace', cascade: ['persist', 'remove'], orphanRemoval: true)]
    public private(set) Collection $walletTransactions;

    private function __construct(string $name, string $siret, string $legalName, string $address, Industry $industry)
    {
        $this->name = trim($name);
        $this->siret = trim($siret);
        $this->legalName = trim($legalName);
        $this->address = trim($address);
        $this->slugId = $this->generate_ulid_prefixed('wrk_');
        $this->industry = $industry;
        $this->balance = 2;

        $this->members = new ArrayCollection();
        $this->invitations = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public static function create(string $name, string $siret, string $legalName, string $address, Industry $industry): self
    {
        return new self($name, $siret, $legalName, $address, $industry);
    }

    /**
     * Ajoute un membre au cabinet.
     * Note: La création de l'objet WorkspaceMember se charge de faire le lien.
     */
    public function addMember(WorkspaceMember $member): void
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
        }
    }

    public function debit(int $amount, string $type, ?string $referenceId = null): void
    {
        if ($amount <= 0) {
            throw new \DomainException("Le montant à débiter doit être strictement positif.");
        }

        if ($this->balance < $amount) {
            throw new InsufficientCreditsException();
        }

        $this->balance -= $amount;

        $this->walletTransactions->add(
            new WalletTransaction(
                workspace: $this,
                amount: $amount,
                type: $type,
                referenceId: $referenceId
            )
        );
    }

    public function credit(int $amount, string $type, ?string $referenceId = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \DomainException("Le montant à créditer doit être strictement positif.");
        }

        $this->balance += $amount;
        $transaction = new WalletTransaction(
            workspace: $this,
            amount: $amount,
            type: $type,
            referenceId: $referenceId
        );

        // 2. On l'ajoute à la collection
        $this->walletTransactions->add($transaction);

        // 3. On retourne l'objet créé
        return $transaction;
    }
}
