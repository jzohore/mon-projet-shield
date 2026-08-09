<?php

declare(strict_types=1);

namespace App\Domain\Kyc\Entity;

use App\Domain\Kyc\Enum\KycFolderStatus;
use App\Domain\Workspace\Entity\Workspace;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Random\RandomException;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'kyc_folders')]
class KycFolder
{
    use GenerateSlugPrefixedTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null {
        get => $this->id;
    }

    #[ORM\Column(length: 20, unique: true)]
    public private(set) string $reference;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public private(set) string $slugId;

    #[ORM\Column(length: 255, nullable: true)]
    public private(set) ?string $companyName = null;

    #[ORM\Column(length: 14, nullable: true)]
    public private(set) ?string $siret = null;

    #[ORM\Column(length: 9, nullable: true)]
    public private(set) ?string $siren = null;

    #[ORM\Column(length: 3, nullable: true)]
    public private(set) ?string $statusAdministratif = null;

    #[ORM\Column(length: 255, nullable: true)]
    public private(set) ?string $address = null;

    #[ORM\Column(type: 'string', nullable: true)]
    public private(set) ?string $legalCategory = null;

    /**
     * @var array<int, array{title: string, description: string, saveAt: \DateTimeImmutable}>
     */
    #[ORM\Column(type: Types::JSON)]
    public private(set) array $history = [];

    #[ORM\Column(length: 255, unique: true)]
    public private(set) ?string $shareToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $shareTokenExpiresAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, Stakeholder>
     */
    #[ORM\OneToMany(targetEntity: Stakeholder::class, mappedBy: 'folder', cascade: ['persist', 'remove'], orphanRemoval: true)]
    public private(set) Collection $stakeholders;

    /**
     * @var Collection<int, KycDocument>
     */
    #[ORM\OneToMany(targetEntity: KycDocument::class, mappedBy: 'folder', cascade: ['persist', 'remove'], orphanRemoval: true)]
    public private(set) Collection $documents;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    public private(set) ?bool $isCertified = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $submittedAt = null;

    /**
     * Le constructeur reste privé pour forcer l'utilisation du Named Constructor.
     *
     * @throws RandomException|\DateMalformedStringException
     */
    private function __construct(#[ORM\ManyToOne(targetEntity: Workspace::class)]
        #[ORM\JoinColumn(nullable: false)]
        public private(set) Workspace $workspace, #[ORM\Column(length: 100)]
        public private(set) string $contactFirstName, #[ORM\Column(length: 100)]
        public private(set) string $contactLastName, #[ORM\Column(length: 255)]
        public private(set) string $contactEmail, #[ORM\Column(type: 'string', enumType: KycFolderStatus::class)]
        public private(set) KycFolderStatus $status)
    {
        $this->stakeholders = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->slugId = $this->generate_ulid_prefixed('kyc_fol_');
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $randomPart = strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        $this->reference = sprintf('KYC-%s-%s', date('y'), $randomPart);
    }

    /**
     * 🪄 Named Constructor : La seule façon d'initialiser un dossier KYC.
     */
    public static function initiate(Workspace $workspace, string $firstName, string $lastName, string $email, KycFolderStatus $status): self
    {
        return new self($workspace, $firstName, $lastName, $email, $status);
    }

    public function saveHistory(string $event, string $data): void
    {
        $this->history[] = [
            'title' => $event,
            'description' => $data,
            'saveAt' => new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        ];
    }

    /**
     * Méthode métier pour lier l'entreprise une fois le formulaire Sirene rempli.
     */
    public function bindCompany(string $companyName, string $siret, string $siren, string $address, string $statusAdministratif, ?string $legalCategory): void
    {
        $this->companyName = strtoupper(trim($companyName));
        $this->siret = $siret;
        $this->siren = $siren;
        $this->legalCategory = $legalCategory;
        $this->address = $address;
        $this->statusAdministratif = $statusAdministratif;
    }

    public function removeCompany(): void
    {
        $this->companyName = null;
        $this->siret = null;
        $this->siren = null;
        $this->legalCategory = null;
        $this->address = null;
        $this->statusAdministratif = null;
        $this->stakeholders->clear();
        $this->documents->clear();
    }

    public function markAsAwaitingClient(): void
    {
        $this->status = KycFolderStatus::AWAITING_CLIENT;
    }

    public function isTokenValid(?string $token, ?\DateTimeImmutable $expiresAt): bool
    {
        if (null === $token || !$expiresAt instanceof \DateTimeImmutable) {
            return false;
        }

        return $expiresAt > new \DateTimeImmutable();
    }

    public function generateShareToken(): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->shareToken = bin2hex(random_bytes(64));
        $this->shareTokenExpiresAt = $now->modify('+7 day');
    }

    public function isShareTokenValid(): bool
    {
        return $this->isTokenValid($this->shareToken, $this->shareTokenExpiresAt);
    }

    public function clearShareToken(): void
    {
        $this->shareToken = null;
        $this->shareTokenExpiresAt = null;
    }

    public function getNormalizedFirstName(): string
    {
        return ucfirst(strtolower(trim($this->contactFirstName)));
    }

    public function getNormalizedLastName(): string
    {
        return strtoupper(trim($this->contactLastName));
    }

    public function getFullName(): string
    {
        return sprintf(
            '%s %s',
            $this->getNormalizedFirstName(),
            $this->getNormalizedLastName()
        );
    }

    public function submitForReview(bool $isCertified): void
    {
        if (!$isCertified) {
            throw new \DomainException('Le dossier doit être certifié pour être soumis.');
        }

        $this->isCertified = true;
        $this->status = KycFolderStatus::IN_REVIEW;
        $this->submittedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        // $this->clearShareToken();
    }
}
