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

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: true)]
    public ?User $owner = null {
        get => $this->owner;
        set => $this->owner = $value;
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt {
        get => $this->createdAt;
    }

    public function __construct(
        User $owner,
        Workspace $workspace,
        string $email,
        InvitedRole $invitedRole,
    ) {
        $this->owner = $owner;
        $this->workspace = $workspace;
        $this->email = $email;
        $this->invitedRole = $invitedRole;
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->slugId = $this->generate_ulid_prefixed('wrk_inv_');
        $this->generateMagicLinkToken();
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

    public function expiresAt()
    {
        $date = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $target = $this->magicLinkTokenExpiresAt;
        $interval = $date->diff($target)->days;

        return sprintf('Le lien expire dans %d jours', $interval);
    }
}
