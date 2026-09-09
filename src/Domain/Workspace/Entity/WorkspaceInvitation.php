<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Entity;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Enum\InvitationStatus;
use App\Domain\Workspace\Enum\InvitedRole;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;

use function Symfony\Component\Clock\now;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'workspaces_invitations')]
#[ORM\Index(name: 'idx_wrk_inv_magic_token', columns: ['magic_link_token'])]
class WorkspaceInvitation
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

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, enumType: InvitationStatus::class)]
    public private(set) InvitationStatus $invitationStatus = InvitationStatus::PENDING;

    /** Hachage SHA-256 du jeton (le jeton en clair ne vit qu'en mémoire, le temps de l'e-mail). */
    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    public private(set) ?string $magicLinkToken;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $magicLinkTokenExpiresAt;

    /** Jeton en clair, jamais persisté : disponible uniquement juste après (re)génération. */
    public private(set) ?string $plainMagicLinkToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $createdAt;

    /** Dernier renvoi de l'invitation : pilote le compte à rebours du bouton « Renvoyer ». */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $lastResentAt = null;

    private function __construct(
        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'invitations')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        public private(set) User $owner,
        #[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'invitations')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        public private(set) Workspace $workspace,
        #[ORM\Column(type: Types::STRING, length: 180)]
        public private(set) string $email,
        #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
        #[Assert\Length(max: 100)]
        public private(set) string $firstName,
        #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
        #[Assert\Length(max: 100)]
        public private(set) string $lastName,
        #[ORM\Column(type: Types::STRING, length: 50, nullable: true, enumType: InvitedRole::class)]
        public private(set) InvitedRole $invitedRole,
    ) {
        $this->createdAt = now();
        $this->slugId = $this->generate_ulid_prefixed('wrk_inv_');
        $this->generateMagicLinkToken();
    }

    public static function create(User $owner, Workspace $workspace, string $email, string $firstName, string $lastName, InvitedRole $invitedRole): self
    {
        return new self(
            owner: $owner,
            workspace: $workspace,
            email: $email,
            firstName: $firstName,
            lastName: $lastName,
            invitedRole: $invitedRole,
        );
    }

    public static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public function isTokenValid(?string $token, ?\DateTimeImmutable $expiresAt): bool
    {
        if (null === $token || !$expiresAt instanceof \DateTimeImmutable) {
            return false;
        }

        return $expiresAt > new \DateTimeImmutable();
    }

    public function generateMagicLinkToken(): void
    {
        $plain = bin2hex(random_bytes(64));
        $this->plainMagicLinkToken = $plain;
        $this->magicLinkToken = self::hashToken($plain);
        $this->magicLinkTokenExpiresAt = now()->modify('+1 day');
    }

    /** Le jeton n'est valide que pour une invitation encore en attente. */
    public function isMagicLinkTokenValid(): bool
    {
        return $this->isPending()
            && $this->isTokenValid($this->magicLinkToken, $this->magicLinkTokenExpiresAt);
    }

    public function clearMagicLinkToken(): void
    {
        $this->magicLinkToken = null;
        $this->magicLinkTokenExpiresAt = null;
        $this->plainMagicLinkToken = null;
    }

    /**
     * @throws \Exception
     */
    public function expiresAt(): string
    {
        $now = now();
        $target = $this->magicLinkTokenExpiresAt;

        if (!$target instanceof \DateTimeImmutable || $target < $now) {
            return 'Le lien est expiré';
        }

        $interval = $now->diff($target);
        $days = (int) $interval->format('%a');

        if (0 === $days) {
            return "Le lien expire aujourd'hui";
        }

        return sprintf('Le lien expire dans %d jours', $days);
    }

    public function isPending(): bool
    {
        return InvitationStatus::PENDING === $this->invitationStatus;
    }

    public function markResent(): void
    {
        $this->lastResentAt = now();
    }

    /**
     * Secondes restantes avant qu'un nouveau renvoi soit permis (anti-spam).
     */
    public function secondsUntilResendAllowed(int $cooldownSeconds = 60): int
    {
        if (!$this->lastResentAt instanceof \DateTimeImmutable) {
            return 0;
        }

        $elapsed = now()->getTimestamp() - $this->lastResentAt->getTimestamp();

        return max(0, $cooldownSeconds - $elapsed);
    }

    public function accept(): void
    {
        $this->invitationStatus = InvitationStatus::ACCEPTED;
    }

    /**
     * Minimisation RGPD : une invitation jamais acceptée et périmée de longue
     * date n'a plus de raison de conserver les nom / prénom / e-mail du
     * destinataire. La ligne devient un simple marqueur historique.
     */
    public function anonymizeAsExpired(): void
    {
        $this->firstName = '';
        $this->lastName = '';
        $this->email = '';
        $this->invitationStatus = InvitationStatus::EXPIRED;
        $this->clearMagicLinkToken();
    }
}
