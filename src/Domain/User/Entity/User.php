<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\ValueObject\UserProfil;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: '`users`')]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cet email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use GenerateSlugPrefixedTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null {
        get => $this->id;
    }

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    public ?string $email = null {
        get => $this->email;
        set => $this->email = strtolower(trim($value ?? ''));
    }

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public ?string $slugId = null {
        get => $this->slugId;
        set => $this->slugId = $value;
    }

    public ?string $password = null {
        get => $this->password;
        set => $this->password = $value;
    }

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column(type: Types::JSON)]
    public array $roles = ['ROLE_USER'] {
        // 👇 On englobe le tout dans array_values()
        get => array_values(array_unique([...$this->roles, 'ROLE_USER']));
        set => $this->roles = array_values(array_unique($value));
    }

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $isVerified = false {
        get => $this->isVerified;
        set => $this->isVerified = $value;
    }

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $isOwner = false {
        get => $this->isOwner;
        set => $this->isOwner = $value;
    }

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public bool $isActif = false {
        get => $this->isActif;
        set => $this->isActif = $value;
    }

    // ==================== PROFIL ====================

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    public ?string $firstName = null {
        get => $this->firstName;
        set => $this->firstName = $value ? ucfirst(trim($value)) : null;
    }

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    public ?string $lastName = null {
        get => $this->lastName;
        set => $this->lastName = $value ? ucfirst(trim($value)) : null;
    }

    // ==================== OAUTH ====================

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public ?string $magicLinkToken = null {
        get => $this->magicLinkToken;
        set => $this->magicLinkToken = $value;
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeImmutable $magicLinkTokenExpiresAt = null {
        get => $this->magicLinkTokenExpiresAt;
        set => $this->magicLinkTokenExpiresAt = $value;
    }

    #[ORM\Column(type: Types::STRING, length: 5, nullable: true)]
    public ?string $lang = null {
        get => $this->lang;
        set => $this->lang = $value;
    }

    // ==================== TIMESTAMPS ====================

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt {
        get => $this->createdAt;
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $updatedAt {
        get => $this->updatedAt;
    }

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, enumType: OnboardingStatus::class)]
    public OnboardingStatus $onboardingStatus = OnboardingStatus::PENDING {
        get => $this->onboardingStatus;
        set => $this->onboardingStatus = $value;
    }

    #[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: true)]
    public ?Workspace $workspace = null {
        get => $this->workspace;
        set => $this->workspace = $value;
    }

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: WorkspaceInvitation::class, mappedBy: 'owner', cascade: ['persist', 'remove'], orphanRemoval: true)]
    public Collection $invitations {
        get => $this->invitations;
    }

    #[ORM\Embedded(class: UserProfil::class, columnPrefix: 'profile_')]
    public UserProfil $profile;

    /**
     * @var list<string>|null $dismissedSteps
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    public ?array $dismissedSteps = [] {
        get => $this->dismissedSteps;
        set => $this->dismissedSteps = $value;
    }

//    /**
//     * @var Collection<int, User>
//     */
//    #[ORM\OneToMany(targetEntity: Device::class, mappedBy: 'device', cascade: ['persist', 'remove'])]
//    public Collection $earlyAccessDevices {
//        get => $this->earlyAccessDevices;
//        set => $this->earlyAccessDevices = $value;
//    }

    private function __construct(
        string $email,
        string $firstName,
        string $lastName,
        bool $isVerified,
    ) {
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->roles = ['ROLE_USER'];
        $this->isVerified = $isVerified;

        $this->slugId = $this->generate_ulid_prefixed('usr_');
        $this->profile = new UserProfil();
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public static function create(string $email, string $firstName, string $lastName): self
    {
        return new self($email, $firstName, $lastName, false);
    }

    /**
     * @return non-empty-string
     */
    public function getUserIdentifier(): string
    {
        if (empty($this->email)) {
            throw new \LogicException('Un utilisateur doit avoir un email.');
        }

        return $this->email;
    }
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
        // Si vous stockez un plainPassword temporaire, nettoyez-le ici
    }

    // ==================== MÉTHODES MÉTIER ====================

    public function getNormalizedEmail(): string
    {
        return strtolower(trim($this->email ?? ''));
    }

    public function getNormalizedFirstName(): string
    {
        return ucfirst(strtoupper(trim($this->firstName ?? '')));
    }

    public function getNormalizedLastName(): ?string
    {
        if (empty($this->lastName)) {
            return null;
        }

        return strtoupper(trim($this->lastName));
    }

    /**
     * Nom complet.
     */
    public function getFullName(): string
    {
        return sprintf(
            '%s %s',
            $this->getNormalizedFirstName(),
            $this->getNormalizedLastName()
        );
    }

    public function getInitials(): string
    {
        $firstName = (string) $this->getNormalizedFirstName();
        $lastName = (string) $this->getNormalizedLastName();

        // On récupère le premier caractère de manière sécurisée (compatible UTF-8)
        $firstLetter = mb_substr($firstName, 0, 1);
        $lastLetter = mb_substr($lastName, 0, 1);

        if ($firstLetter === '' && $lastLetter === '') {
            return '??'; // Valeur de repli (fallback)
        }

        return sprintf(
            '%s%s',
            mb_strtoupper($firstLetter),
            mb_strtoupper($lastLetter)
        );
    }

    public function isTokenValid(?string $token, ?\DateTimeImmutable $expiresAt): bool
    {
        if (null === $token || null === $expiresAt) {
            return false;
        }

        return $expiresAt > new \DateTimeImmutable();
    }

    public function generateMagicLinkToken(): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->magicLinkToken = bin2hex(random_bytes(64));
        $this->magicLinkTokenExpiresAt = $now->add(new \DateInterval('PT10M'));
    }

    public function isMagicLinkTokenValid(): bool
    {
        return $this->isTokenValid($this->magicLinkToken, $this->magicLinkTokenExpiresAt);
    }

    public function clearMagicLinkToken(): void
    {
        $this->magicLinkToken = null;
        $this->magicLinkTokenExpiresAt = null;
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }

    public function promoteToAdmin(): void
    {
        if (!in_array('ROLE_ADMIN', $this->roles, true)) {
            $this->roles = array_values(array_unique([...$this->roles, 'ROLE_ADMIN']));
        }
    }

    public function isStepDismissed(?string $stepId): bool
    {
        \Webmozart\Assert\Assert::notNull($this->slugId);

        // Le ?? [] garantit que le "haystack" est toujours un array
        return in_array($stepId, $this->dismissedSteps ?? [], true);
    }
}
