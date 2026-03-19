<?php

namespace App\Domain\Workspace\Entity;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Enum\InvitedRole;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'workspace_members')]
// 🛡️ SÉCURITÉ ABSOLUE : Un utilisateur ne peut avoir qu'un seul rôle par espace de travail
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

    // 💡 IMMUTABILITÉ : Utilisation de "readonly".
    // Une fois défini dans le constructeur, impossible de changer l'utilisateur de cette ligne.
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public readonly User $user;

    #[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public readonly Workspace $workspace;

    // Le rôle peut évoluer (ex: on promeut un collaborateur en Admin), donc pas de "readonly".
    // Doctrine gère nativement l'Enum PHP.
    #[ORM\Column(type: Types::STRING, length: 50, enumType: InvitedRole::class)]
    public InvitedRole $role {
        get => $this->role;
        set => $this->role = $value;
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $joinedAt {
        get => $this->joinedAt;
    }

    private function __construct(User $user, Workspace $workspace, InvitedRole $role)
    {
        $this->user = $user;
        $this->workspace = $workspace;
        $this->role = $role;
        $this->joinedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    /**
     * 🚀 ÉTAPE 2 : La "Factory Method" (Named Constructor)
     * C'est la seule porte d'entrée pour créer un membre.
     */
    public static function create(Workspace $workspace, User $user, InvitedRole $role): self
    {
        // Tu peux rajouter des règles métier ici avant la création !
        // ex: if ($workspace->isArchived()) throw new DomainException(...);

        $member = new self($user, $workspace, $role);

        // Bonus DDD : On lie automatiquement le membre au Workspace
        $workspace->addMember($member);

        return $member;
    }
    /**
     * Helper DDD pratique pour éviter d'écrire la comparaison partout dans le code
     */
    public function isAdmin(): bool
    {
        return $this->role === InvitedRole::ROLE_WORKSPACE_ADMIN;
    }
}
