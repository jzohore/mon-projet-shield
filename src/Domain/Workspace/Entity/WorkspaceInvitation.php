<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Entity;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Enum\InvitationStatus;
use App\Domain\Workspace\Enum\InvitedRole;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Clock\now;

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
    public private(set) string $email;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    public private(set) string $firstName;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    public private(set) string $lastName;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public private(set) string $slugId;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, enumType: InvitationStatus::class)]
    public private(set) InvitationStatus $invitationStatus = InvitationStatus::PENDING;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, enumType: InvitedRole::class)]
    public private(set) InvitedRole $invitedRole = InvitedRole::ROLE_WORKSPACE_COLLAB;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public private(set) ?string $magicLinkToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?DateTimeImmutable $magicLinkTokenExpiresAt = null;

    #[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'invitations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public private(set) Workspace $workspace;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'invitations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public private(set) User $owner;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) DateTimeImmutable $createdAt;

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
        $this->createdAt = now();
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

    public function isTokenValid(?string $token, ?DateTimeImmutable $expiresAt): bool
    {
        if (null === $token || null === $expiresAt) {
            return false;
        }

        return $expiresAt > new DateTimeImmutable();
    }

    public function generateMagicLinkToken(): void
    {
        $this->magicLinkToken = bin2hex(random_bytes(64));
        $this->magicLinkTokenExpiresAt = now()->modify('+1 day');
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

    /**
     * @throws Exception
     */
    public function expiresAt(): string
    {
        $now = now();
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

    public function isPending(): bool
    {
        return $this->invitationStatus === InvitationStatus::PENDING;
    }

    public function accept(): void
    {
        $this->invitationStatus = InvitationStatus::ACCEPTED;
    }
}
