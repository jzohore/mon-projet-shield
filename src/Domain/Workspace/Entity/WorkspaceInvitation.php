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
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'workspaces_invitations')]
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

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    public ?string $email = null {
        get => $this->email;
        set => $this->email = strtolower(trim($value ?? ''));
    }

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

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public ?string $slugId = null {
        get => $this->slugId;
    }

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, enumType: InvitationStatus::class)]
    public InvitationStatus $invitationStatus = InvitationStatus::PENDING {
        get => $this->invitationStatus;
        set => $this->invitationStatus = $value;
    }

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, enumType: InvitedRole::class)]
    public ?InvitedRole $invitedRole = null {
        get => $this->invitedRole;
        set => $this->invitedRole = $value;
    }

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

    #[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'invitations')]
    #[ORM\JoinColumn(nullable: true)]
    public ?Workspace $workspace = null {
        get => $this->workspace;
        set => $this->workspace = $value;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'invitations')]
    #[ORM\JoinColumn(nullable: true)]
    public ?User $owner = null {
        get => $this->owner;
        set => $this->owner = $value;
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt {
        get => $this->createdAt;
    }

    private function __construct(
        User $owner,
        Workspace $workspace,
        string $email,
        string $firstName,
        string $lastName,
        InvitedRole $invitedRole,
    ) {
        $this->owner = $owner;
        $this->workspace = $workspace;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->invitedRole = $invitedRole;
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->slugId = $this->generate_ulid_prefixed('wrk_inv_');
        $this->generateMagicLinkToken();
    }

    public static function create(User $owner, Workspace $workspace, string $email, string $firstName, string $lastName, InvitedRole $invitedRole): WorkspaceInvitation
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

    public function isTokenValid(?string $token, ?\DateTimeImmutable $expiresAt): bool
    {
        if (null === $token || null === $expiresAt) {
            return false;
        }

        return $expiresAt > new \DateTimeImmutable();
    }

    public function generateMagicLinkToken(): void
    {
        $this->magicLinkToken = bin2hex(random_bytes(64));
        $this->magicLinkTokenExpiresAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'))->modify('+6 day');
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

    public function expiresAt(): string
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $target = $this->magicLinkTokenExpiresAt;

        if (null === $target || $target < $now) {
            return 'Le lien est expiré';
        }

        $interval = $now->diff($target);
        $days = (int) $interval->format('%a');

        if ($days === 0) {
            return "Le lien expire aujourd'hui";
        }

        return sprintf('Le lien expire dans %d jours', $days);
    }
}
