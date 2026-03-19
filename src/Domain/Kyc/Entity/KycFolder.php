<?php

namespace App\Domain\Kyc\Entity;

use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Kyc\Enum\KycFolderStatus;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
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

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public private(set) string $slugId;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false)]
    public private(set) Workspace $workspace;

    #[ORM\Column(length: 100)]
    public private(set) string $contactFirstName;

    #[ORM\Column(length: 100)]
    public private(set) string $contactLastName;

    #[ORM\Column(length: 255)]
    public private(set) string $contactEmail;

    #[ORM\Column(length: 255, nullable: true)]
    public private(set) ?string $companyName = null;

    #[ORM\Column(length: 14, nullable: true)]
    public private(set) ?string $siret = null;

    #[ORM\Column(type: 'string', enumType: KycFolderStatus::class)]
    public private(set) KycFolderStatus $status = KycFolderStatus::DRAFT;

    #[ORM\Column(length: 64, unique: true)]
    public private(set) ?string $shareToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $shareTokenExpiresAt = null;

    /**
     * @var Collection<int, Stakeholder>
     */
    #[ORM\OneToMany(targetEntity: Stakeholder::class, mappedBy: 'folder', cascade: ['persist', 'remove'])]
    public private(set) Collection $stakeholders;

    /**
     * @var Collection<int, KycDocument>
     */
    #[ORM\OneToMany(targetEntity: KycDocument::class, mappedBy: 'folder', cascade: ['persist', 'remove'])]
    public private(set) Collection $documents;

    /**
     * Le constructeur reste privé pour forcer l'utilisation du Named Constructor.
     */
    private function __construct(Workspace $workspace, string $contactFirstName, string $contactLastName, string $contactEmail)
    {
        $this->workspace = $workspace;
        $this->contactFirstName = $contactFirstName;
        $this->contactLastName = $contactLastName;
        $this->contactEmail = $contactEmail;
        $this->stakeholders = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->slugId = $this->generate_ulid_prefixed('kyc_fol_');
    }

    /**
     * 🪄 Named Constructor : La seule façon d'initialiser un dossier KYC
     */
    public static function initiate(Workspace $workspace, string $firstName, string $lastName, string $email): self
    {
        return new self($workspace, $firstName, $lastName, $email);
    }

    /**
     * Méthode métier pour lier l'entreprise une fois le formulaire Sirene rempli.
     */
    public function bindCompany(string $companyName, string $siret): void
    {
        $this->companyName = $companyName;
        $this->siret = $siret;
    }

    public function markAsAwaitingClient(): void
    {
        $this->status = KycFolderStatus::AWAITING_CLIENT;
    }

    public function isTokenValid(?string $token, ?\DateTimeImmutable $expiresAt): bool
    {
        if (null === $token || null === $expiresAt) {
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
}
