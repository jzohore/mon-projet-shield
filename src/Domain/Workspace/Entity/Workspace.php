<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Entity;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\Billing\Entity\Subscription;
use App\Domain\Billing\Enum\CreditAction;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Firm\Entity\RegulatoryProfile;
use App\Domain\Screening\Entity\ScreeningAudit;
use App\Domain\Support\Entity\SupportThread;
use App\Domain\User\Entity\Client;
use App\Domain\Wallet\Entity\WalletTransaction;
use App\Domain\Wallet\Exception\InsufficientCreditsException;
use App\Domain\Workspace\Enum\Industry;
use App\Domain\Workspace\Enum\WorkspaceType;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;

use function Symfony\Component\Clock\now;

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
    #[ORM\Column(type: Types::STRING, length: 255)]
    public private(set) string $name;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public private(set) string $slugId;

    // --- DONNÉES REGTECH & FACTURATION ---

    #[ORM\Column(type: Types::STRING, length: 14, unique: true, nullable: true)]
    public private(set) ?string $siret = null;

    #[ORM\Column(type: Types::STRING, length: 14, unique: true, nullable: true)]
    public private(set) ?string $siren = null;

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

    /**
     * @var Collection<int, LegalUpdateDemand>
     */
    #[ORM\OneToMany(targetEntity: LegalUpdateDemand::class, mappedBy: 'workspace', cascade: ['persist', 'remove'])]
    public private(set) Collection $legalUpdateDemands;

    /**
     * @var Collection<int, ScreeningAudit>
     */
    #[ORM\OneToMany(targetEntity: ScreeningAudit::class, mappedBy: 'workspace', cascade: ['persist', 'remove'])]
    public Collection $screeningAudits {
        get => $this->screeningAudits;
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt {
        get => $this->createdAt;
    }

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public private(set) int $balance = 2;

    /**
     * @var array<int|string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    public private(set) ?array $transactions = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['default' => false])]
    public private(set) bool $isActive = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['default' => true])]
    public private(set) bool $isSiretValid = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $verifySiretLastAttemptedAt = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public private(set) ?string $suspensionReason = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $suspendedAt = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: WorkspaceType::class)]
    public private(set) WorkspaceType $type = WorkspaceType::INDIVIDUAL;

    #[ORM\Column(type: Types::STRING, length: 64, unique: true, nullable: true)]
    public private(set) string $publicToken;

    /**
     * @var Collection<int, WalletTransaction>
     */
    #[ORM\OneToMany(targetEntity: WalletTransaction::class, mappedBy: 'workspace', cascade: ['persist', 'remove'], orphanRemoval: true)]
    public private(set) Collection $walletTransactions;

    #[ORM\OneToOne(targetEntity: Subscription::class, mappedBy: 'workspace', cascade: ['persist', 'remove'], orphanRemoval: true)]
    public private(set) ?Subscription $subscription = null;

    /**
     * @var Collection<int, SupportThread>
     */
    #[ORM\OneToMany(targetEntity: SupportThread::class, mappedBy: 'workspace', cascade: ['persist', 'remove'], orphanRemoval: true)]
    public private(set) Collection $supportThread;

    /** @var Collection<int, ComplianceFolder> */
    #[ORM\OneToMany(targetEntity: ComplianceFolder::class, mappedBy: 'workspace', cascade: ['persist', 'remove'])]
    public private(set) Collection $folders;

    /** @var Collection<int, ComplianceFolder> */
    #[ORM\OneToMany(targetEntity: AuditLog::class, mappedBy: 'workspace', cascade: ['persist', 'remove'])]
    public private(set) Collection $auditLogs;

    #[ORM\OneToOne(targetEntity: RegulatoryProfile::class, mappedBy: 'workspace', cascade: ['persist', 'remove'])]
    public private(set) ?RegulatoryProfile $regulatoryProfile = null;

    /**
     * @var Collection<int, Client>
     */
    #[ORM\ManyToMany(targetEntity: Client::class, mappedBy: 'workspaces')]
    public private(set) Collection $clients;

    #[ORM\Column(options: ['default' => false])]
    public private(set) bool $hasClaimed2faBonus = false;

    private function __construct(string $name, string $legalName, string $address, #[ORM\Column(type: Types::STRING, length: 14, nullable: true)]
        public private(set) string $etatAdministratif, Industry $industry, #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
        public private(set) string $email)
    {
        $this->name = trim($name);
        $this->legalName = trim($legalName);
        $this->address = trim($address);
        $this->slugId = $this->generate_ulid_prefixed('wrk_');
        $this->industry = $industry;
        $this->publicToken = bin2hex(random_bytes(32));

        $this->members = new ArrayCollection();
        $this->invitations = new ArrayCollection();
        $this->walletTransactions = new ArrayCollection();
        $this->supportThread = new ArrayCollection();
        $this->folders = new ArrayCollection();
        $this->auditLogs = new ArrayCollection();
        $this->createdAt = now();
    }

    public static function create(string $name, string $legalName, string $address, string $etatAdministratif, Industry $industry, string $email): self
    {
        return new self($name, $legalName, $address, $etatAdministratif, $industry, trim($email));
    }

    public function update(string $name, string $address, Industry $industry, ?string $siret = null, ?string $siren = null): void
    {
        $this->name = $name;
        $this->siret = $siret;
        $this->siren = $siren;
        $this->address = $address;
        $this->industry = $industry;
        $this->legalName = $name;
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

    public function debit(CreditAction $action, string $type): void
    {
        $cost = $action->getAmount();

        // Si l'action est gratuite, on ne fait rien
        if (0 === $cost) {
            return;
        }

        if ($action->getAmount() <= 0) {
            throw new \DomainException('Le montant à débiter doit être strictement positif.');
        }

        if ($this->balance < $cost) {
            throw new InsufficientCreditsException();
        }

        $this->balance -= $cost;

        $this->walletTransactions->add(
            new WalletTransaction(
                workspace: $this,
                amount: $cost,
                type: $type,
                action: 'debit'
            )
        );
    }

    public function credit(int $amount, string $type, ?string $invoiceUrl = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \DomainException('Le montant à créditer doit être strictement positif.');
        }

        $this->balance += $amount;
        $transaction = new WalletTransaction(
            workspace: $this,
            amount: $amount,
            type: $type,
            action: 'credit',
            invoiceUrl: $invoiceUrl
        );

        // 2. On l'ajoute à la collection
        $this->walletTransactions->add($transaction);

        // 3. On retourne l'objet créé
        return $transaction;
    }

    public function addWorkspaceType(WorkspaceType $type): void
    {
        $this->type = $type;
    }

    public function markAsActive(): void
    {
        $this->isActive = true;
    }

    public function markAsUnActive(): void
    {
        $this->isActive = false;
    }

    public function isFirm(): bool
    {
        return WorkspaceType::FIRM === $this->type;
    }

    public function setVerifySiretLastAttemptedAt(): void
    {
        $this->verifySiretLastAttemptedAt = now();
    }

    public function setIsSiretValid(bool $value): void
    {
        $this->isSiretValid = $value;
    }

    public function updateEtatAdministratif(string $etatAdministratif): void
    {
        $this->etatAdministratif = $etatAdministratif;
    }

    public function updateSiret(string $siret): void
    {
        $this->siret = $siret;
    }

    public function updateSiren(string $siren): void
    {
        $this->siren = $siren;
    }

    /**
     * Suspend le Workspace suite à une anomalie légale ou de facturation.
     */
    public function updateSiretStatus(bool $isSiretValid, string $etatAdministratif): void
    {
        $this->isSiretValid = $isSiretValid;
        $this->etatAdministratif = $etatAdministratif;
        $this->setVerifySiretLastAttemptedAt();
    }

    public function markSiretAsInvalid(string $etatAdministratif, string $reason): void
    {
        $this->isSiretValid = false;
        $this->etatAdministratif = $etatAdministratif;
        $this->suspensionReason = $reason;
        $this->suspendedAt = now(); // Transition d'état métier explicit
    }

    public function suspend(string $reason): void
    {
        if (!$this->isActive) {
            return;
        }

        $this->isActive = false;
        $this->suspensionReason = trim($reason);
        $this->suspendedAt = now();
    }

    /**
     * Permet à un administrateur de débloquer le compte en cas de résolution du problème.
     */
    public function reactivate(): void
    {
        $this->isActive = true;
        $this->suspensionReason = null;
        $this->suspendedAt = null;

        // $this->addAuditLog('Réactivation manuelle du compte.');
    }

    public function changeLegalEntity(
        string $newSiret,
        string $newSiren,
        string $newName,
        string $newAddress,
        string $etatAdministratif,
    ): void {
        $this->siret = $newSiret;
        $this->siren = $newSiren;
        $this->name = $newName;           // Le nom usuel du Workspace change
        $this->legalName = $newName;      // Le nom juridique change
        $this->address = $newAddress;     // La nouvelle adresse du siège

        $this->isSiretValid = true;
        $this->etatAdministratif = $etatAdministratif;
        $this->suspensionReason = null;
        $this->suspendedAt = null;
        $this->isActive = true;
    }

    public function regeneratePublicToken(): void
    {
        $this->publicToken = bin2hex(random_bytes(32));
    }

    public function addClient(Client $client): void
    {
        if (!$this->clients->contains($client)) {
            $this->clients->add($client);
        }
    }

    /**
     * Seul le UseCase est autorisé à modifier ces données via cette méthode métier.
     */
    public function updateLegalDetails(string $name, string $siret): void
    {
        $this->name = $name;
        $this->siret = $siret;
    }

    public function isOrgCompleted(): bool
    {
        return !in_array($this->siret, [null, '', '0'], true)
            && !in_array($this->siren, [null, '', '0'], true);
    }

    public function claimTwoFactorBonus(): void
    {
        if ($this->hasClaimed2faBonus) {
            return;
        }

        $this->hasClaimed2faBonus = true;
        ++$this->balance;
    }
}
