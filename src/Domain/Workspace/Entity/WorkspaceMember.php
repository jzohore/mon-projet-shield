<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Entity;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Enum\InvitedRole;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;

use function Symfony\Component\Clock\now;

use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'workspace_members')]
#[ORM\UniqueConstraint(name: 'idx_unique_user_workspace', columns: ['user_id', 'workspace_id'])]
class WorkspaceMember
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null {
        get => $this->id;
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $joinedAt;

    /**
     * @throws \Exception
     */
    private function __construct(#[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'members')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        public private(set) User $user, #[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'members')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        public private(set) Workspace $workspace, #[ORM\Column(type: Types::STRING, length: 50, enumType: InvitedRole::class)]
        public private(set) InvitedRole $role)
    {
        $this->joinedAt = now();
    }

    /**
     * @throws \Exception
     */
    public static function create(Workspace $workspace, User $user, InvitedRole $role): self
    {
        $member = new self($user, $workspace, $role);

        // Bonus DDD : On lie automatiquement le membre au Workspace
        $workspace->addMember($member);

        return $member;
    }

    public function isAdmin(): bool
    {
        return InvitedRole::ROLE_WORKSPACE_ADMIN === $this->role;
    }
}
