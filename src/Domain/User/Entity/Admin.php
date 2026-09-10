<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use App\Domain\User\Enum\AdminRole;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Random\RandomException;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use function Symfony\Component\Clock\now;

use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

#[ORM\Entity]
#[ORM\Table(name: '`admins`')] // Table séparée !
#[UniqueEntity(fields: ['email'], message: 'Un compte client existe déjà avec cet email.')]
class Admin implements UserInterface, TwoFactorInterface, EquatableInterface
{
    use GenerateSlugPrefixedTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null {
        get {
            if (!$this->id instanceof Uuid) {
                throw new \LogicException('L\'ID du client n\'a pas encore été généré.');
            }

            return $this->id;
        }
    }

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public private(set) string $slugId;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    public private(set) ?string $phoneNumber = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column(type: Types::JSON)]
    public private(set) array $roles = ['ROLE_SUPER_ADMIN'];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public private(set) ?string $magicLinkToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $magicLinkTokenExpiresAt = null;

    // ==================== TIMESTAMPS ====================

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::STRING, length: 150, nullable: true)]
    public private(set) ?string $googleAuthenticatorSecret = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    public private(set) bool $isTotpVerified = false;

    /**
     * Empreinte d'identité de session : la régénérer invalide toutes les sessions
     * ouvertes de l'administrateur au prochain rafraîchissement (cf. isEqualTo()).
     */
    #[ORM\Column(type: Types::STRING, length: 32, options: ['default' => ''])]
    public private(set) string $securityStamp = '';

    /** Suspension : le compte existe mais ne peut plus se connecter. */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $suspendedAt = null;

    /** Archivage : sortie de l'équipe, conservé pour la piste d'audit. */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $archivedAt = null;

    public bool $isSuspended {
        get => $this->suspendedAt instanceof \DateTimeImmutable;
    }

    public bool $isArchived {
        get => $this->archivedAt instanceof \DateTimeImmutable;
    }

    private function __construct(#[ORM\Column(type: Types::STRING, length: 180, unique: true)]
        public private(set) string $email, #[ORM\Column(type: Types::STRING, length: 100)]
        public private(set) string $firstName, #[ORM\Column(type: Types::STRING, length: 100)]
        public private(set) string $lastName, #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
        public private(set) bool $isActif = false)
    {
        $this->createdAt = now();
        $this->securityStamp = bin2hex(random_bytes(16));
        // Initialisation du slug (selon comment ton Trait fonctionne)
        $this->slugId = $this->generate_ulid_prefixed('adm_');
    }

    /**
     * @param list<string> $roles rôles Symfony (cf. AdminRole) ; au moins un
     */
    public static function initiate(
        string $email,
        string $firstName,
        string $lastName,
        bool $isActif = false,
        array $roles = [AdminRole::OPERATOR->value],
    ): self {
        $admin = new self($email, $firstName, $lastName, $isActif);
        $admin->changeRoles($roles);

        return $admin;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = AdminRole::sanitize($this->roles);

        return [] === $roles ? [AdminRole::OPERATOR->value] : $roles;
    }

    /**
     * @param list<string> $roles
     */
    public function changeRoles(array $roles): void
    {
        $sanitized = AdminRole::sanitize($roles);
        Assert::notEmpty($sanitized, 'Un administrateur doit avoir au moins un rôle valide.');

        $this->roles = $sanitized;
        $this->regenerateSecurityStamp();
    }

    /**
     * Régénère l'empreinte de session : coupe les sessions ouvertes au prochain
     * rafraîchissement (suspension, changement de rôle, archivage).
     */
    public function regenerateSecurityStamp(): void
    {
        $this->securityStamp = bin2hex(random_bytes(16));
    }

    /** Suspend le compte : connexion impossible, sessions ouvertes coupées. */
    public function suspend(): void
    {
        $this->isActif = false;
        $this->suspendedAt = now();
        $this->regenerateSecurityStamp();
    }

    /** Réactive un compte suspendu. */
    public function reactivate(): void
    {
        $this->isActif = true;
        $this->suspendedAt = null;
        $this->regenerateSecurityStamp();
    }

    /** Archive le compte : sortie de l'équipe, conservé pour l'audit. */
    public function archive(): void
    {
        $this->isActif = false;
        $this->archivedAt = now();
        if (!$this->suspendedAt instanceof \DateTimeImmutable) {
            $this->suspendedAt = now();
        }
        $this->clearMagicLinkToken();
        $this->regenerateSecurityStamp();
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof self) {
            return false;
        }

        if ($this->email !== $user->email) {
            return false;
        }

        // Tolérance transitoire : un compte pas encore ré-empreinté (déploiement
        // en cours) ne doit pas être déconnecté à tort.
        if ('' === $this->securityStamp || '' === $user->securityStamp) {
            return true;
        }

        return hash_equals($this->securityStamp, $user->securityStamp);
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

    public function updateProfile(string $firstName, string $lastName, ?string $phoneNumber): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->phoneNumber = $phoneNumber;
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

    public function getNormalizedFirstName(): string
    {
        return ucfirst(strtoupper(trim($this->firstName)));
    }

    public function getNormalizedLastName(): string
    {
        return ucfirst(strtoupper(trim($this->lastName)));
    }

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
        $firstName = $this->getNormalizedFirstName();
        $lastName = $this->getNormalizedLastName();

        $firstLetter = mb_substr($firstName, 0, 1);
        $lastLetter = mb_substr($lastName, 0, 1);

        if ('' === $firstLetter && '' === $lastLetter) {
            return '??'; // Valeur de repli (fallback)
        }

        return sprintf(
            '%s%s',
            mb_strtoupper($firstLetter),
            mb_strtoupper($lastLetter)
        );
    }
}
