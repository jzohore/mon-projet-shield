<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Screening\Entity\ScreeningAudit;
use App\Domain\Support\Entity\SupportThread;
use App\Domain\User\Enum\JobRole;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\ValueObject\UserProfil;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Random\RandomException;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use function Symfony\Component\Clock\now;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: '`users`')]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cet email')]
class User implements UserInterface, TwoFactorInterface, \Stringable
{
    use GenerateSlugPrefixedTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null {
        get {
            if (!$this->id instanceof Uuid) {
                throw new \LogicException('L\'ID de l\'entité n\'a pas encore été généré.');
            }

            return $this->id;
        }
    }

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public private(set) string $slugId;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column(type: Types::JSON)]
    public array $roles = ['ROLE_USER'] {
        get => array_values(array_unique([...$this->roles, 'ROLE_USER']));
        set => $this->roles = array_values(array_unique($value));
    }

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public private(set) bool $isOwner = false;

    // ==================== OAUTH ====================

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public private(set) ?string $magicLinkToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $magicLinkTokenExpiresAt = null;

    // ==================== TIMESTAMPS ====================

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $updatedAt;
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $onboardingReminderSentAt = null;

    #[ORM\Column(type: Types::STRING, length: 150, nullable: true)]
    public private(set) ?string $googleAuthenticatorSecret = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public private(set) bool $isTotpVerified = false;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public private(set) int $trustedVersion = 0;

    #[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: true)]
    public private(set) ?Workspace $workspace = null;

    /**
     * @var Collection<int, WorkspaceInvitation>
     */
    #[ORM\OneToMany(targetEntity: WorkspaceInvitation::class, mappedBy: 'owner', cascade: ['persist', 'remove'], orphanRemoval: true)]
    public private(set) Collection $invitations;
    /**
     * @var Collection<int, ScreeningAudit>
     */
    #[ORM\OneToMany(targetEntity: ScreeningAudit::class, mappedBy: 'owner', cascade: ['persist', 'remove'])]
    public private(set) Collection $screeningAudits;

    #[ORM\Embedded(class: UserProfil::class, columnPrefix: 'profile_')]
    public private(set) UserProfil $profile;

    /**
     * @var Collection<int, SupportThread>
     */
    #[ORM\OneToMany(targetEntity: SupportThread::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    public private(set) Collection $supportThread;

    /**
     * @var Collection<int, WorkspaceMember>
     */
    #[ORM\OneToMany(targetEntity: WorkspaceMember::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    public private(set) Collection $members;

    /**
     * @var Collection<int, ComplianceFolder>
     */
    #[ORM\OneToMany(targetEntity: ComplianceFolder::class, mappedBy: 'assignedReviewer', cascade: ['persist', 'remove'])]
    public private(set) Collection $folders;

    /**
     * @param list<string> $roles
     *
     * @throws \Exception
     */
    private function __construct(
        #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
        public private(set) string $email,
        #[ORM\Column(type: Types::STRING, length: 100)]
        #[Assert\Length(max: 100)]
        public private(set) string $firstName,
        #[ORM\Column(type: Types::STRING, length: 100)]
        #[Assert\Length(max: 100)]
        public private(set) string $lastName,
        #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
        public private(set) bool $isVerified,
        array $roles = ['ROLE_USER'],
        #[ORM\Column(type: Types::STRING, length: 50, nullable: true, enumType: OnboardingStatus::class)]
        public private(set) OnboardingStatus $onboardingStatus = OnboardingStatus::PENDING,
        #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
        public private(set) bool $isActif = false,
    ) {
        $this->roles = array_values(array_unique($roles));
        $this->slugId = $this->generate_ulid_prefixed('usr_');
        $this->profile = new UserProfil();
        $this->createdAt = now();
        $this->updatedAt = now();
        $this->supportThread = new ArrayCollection();
        $this->invitations = new ArrayCollection();
        $this->screeningAudits = new ArrayCollection();
        $this->members = new ArrayCollection();
    }

    /**
     * @param list<string> $roles
     *
     * @throws \Exception
     */
    public static function create(
        string $email,
        string $firstName,
        string $lastName,
        bool $isVerified = false,
        array $roles = ['ROLE_USER'],
        OnboardingStatus $onboardingStatus = OnboardingStatus::PENDING,
        bool $isActif = false,
    ): self {
        return new self($email, $firstName, $lastName, $isVerified, $roles, $onboardingStatus, $isActif);
    }

    /**
     * @return non-empty-string
     */
    public function getUserIdentifier(): string
    {
        if ('' === $this->email || '0' === $this->email) {
            throw new \LogicException('Un utilisateur doit avoir un email.');
        }

        return $this->email;
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
        return $this->email;
    }

    /**
     * Formatage pour affichage : Prénom avec première lettre en majuscule (ex: "Jean-Pierre").
     */
    public function getNormalizedFirstName(): string
    {
        return mb_convert_case($this->firstName, \MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Formatage réglementaire : Nom entièrement en majuscules (ex: "DUPONT").
     */
    public function getNormalizedLastName(): string
    {
        return mb_strtoupper($this->lastName, 'UTF-8');
    }

    /**
     * Nom complet au format légal pour rapports LCB-FT et DER : "Jean-Pierre DUPONT".
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
        $firstLetter = mb_substr($this->firstName, 0, 1, 'UTF-8');
        $lastLetter = mb_substr($this->lastName, 0, 1, 'UTF-8');

        if ('' === $firstLetter && '' === $lastLetter) {
            return 'K';
        }

        return mb_strtoupper($firstLetter . $lastLetter, 'UTF-8');
    }

    public function isTokenValid(?string $token, ?\DateTimeImmutable $expiresAt): bool
    {
        if (null === $token || !$expiresAt instanceof \DateTimeImmutable) {
            return false;
        }

        return $expiresAt > new \DateTimeImmutable();
    }

    /**
     * @throws \Exception
     * @throws RandomException
     */
    public function generateMagicLinkToken(): void
    {
        $this->magicLinkToken = bin2hex(random_bytes(64));
        $this->magicLinkTokenExpiresAt = now()->add(new \DateInterval('PT10M'));
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
        if (!in_array('ROLE_SUPER_ADMIN', $this->roles, true)) {
            $this->roles = array_values(array_unique([...$this->roles, 'ROLE_SUPER_ADMIN']));
        }
        $this->onboardingStatus = OnboardingStatus::COMPLETED;
    }

    public function updateProfilInformations(string $firstName, string $lastName, ?JobRole $jobRole = null, ?string $phone = null): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->profile->jobTitle = $jobRole;
        $this->profile->phoneNumber = $phone;
    }

    public function dismissOnboarding(): void
    {
        $this->profile->setDismissOnboarding(true);
    }

    public function setOnboardingReminderSentAt(\DateTimeImmutable $date): void
    {
        $this->onboardingReminderSentAt = $date;
    }

    public function updateOnboardStatus(OnboardingStatus $status): void
    {
        $this->onboardingStatus = $status;
    }

    public function markAsOnboardingCompleted(): void
    {
        $this->onboardingStatus = OnboardingStatus::COMPLETED;
    }

    public function enabledProfil(): void
    {
        $this->isVerified = true;
        $this->isOwner = true;
        $this->isActif = true;
    }

    public function setIsTotpVerified(bool $isTotpVerified): void
    {
        $this->isTotpVerified = $isTotpVerified;
    }

    // 2. 🚨 LE POINT CLÉ : On modifie l'interface de Scheb
    public function isGoogleAuthenticatorEnabled(): bool
    {
        // Scheb n'imposera le 2FA à la connexion QUE si cette méthode renvoie true.
        // Donc on exige le secret ET la vérification !
        return null !== $this->getGoogleAuthenticatorSecret() && $this->isTotpVerified;
    }

    public function getGoogleAuthenticatorUsername(): ?string
    {
        return $this->email;
    }

    public function getGoogleAuthenticatorSecret(): ?string
    {
        return $this->googleAuthenticatorSecret;
    }

    public function setGoogleAuthenticatorSecret(?string $googleAuthenticatorSecret): void
    {
        $this->googleAuthenticatorSecret = $googleAuthenticatorSecret;
    }
}
