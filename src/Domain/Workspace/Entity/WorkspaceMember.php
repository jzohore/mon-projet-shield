<?php

namespace App\Domain\Workspace\Entity;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Enum\InvitedRole;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

use function Symfony\Component\Clock\now;

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

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public private(set) User $user;

    #[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public private(set) Workspace $workspace;


    #[ORM\Column(type: Types::STRING, length: 50, enumType: InvitedRole::class)]
    public private(set) InvitedRole $role;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) DateTimeImmutable $joinedAt;

    /**
     * @param User $user
     * @param Workspace $workspace
     * @param InvitedRole $role
     * @throws Exception
     */
    private function __construct(User $user, Workspace $workspace, InvitedRole $role)
    {
        $this->user = $user;
        $this->workspace = $workspace;
        $this->role = $role;
        $this->joinedAt = now();
    }

    /**
     * @param Workspace $workspace
     * @param User $user
     * @param InvitedRole $role
     * @return self
     * @throws Exception
     */
    public static function create(Workspace $workspace, User $user, InvitedRole $role): self
    {
        $member = new self($user, $workspace, $role);

        // Bonus DDD : On lie automatiquement le membre au Workspace
        $workspace->addMember($member);

        return $member;
    }

    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === InvitedRole::ROLE_WORKSPACE_ADMIN;
    }
}
