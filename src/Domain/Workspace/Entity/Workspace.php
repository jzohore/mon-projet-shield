<?php

namespace App\Domain\Workspace\Entity;

use App\Domain\User\Entity\User;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'workspaces')]
class Workspace
{
    use GenerateSlugPrefixedTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null {
        get => $this->id;
    }

    // Le nom de l'entreprise (ex: "Cabinet Dupont & Associés")
    #[ORM\Column(type: Types::STRING, length: 255)]
    public ?string $name = null {
        get => $this->name;
        set => $this->name = trim($value ?? '');
    }

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public ?string $slugId = null {
        get => $this->slugId;
    }

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'workspace')]
    public Collection $members {
        get => $this->members;
    }

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: WorkspaceInvitation::class, mappedBy: 'workspace', cascade: ['persist', 'remove'])]
    public Collection $invitations {
        get => $this->invitations;
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public \DateTimeImmutable $createdAt {
        get => $this->createdAt;
    }

    public function __construct(string $name)
    {
        $this->name = trim($name);

        // Préfixe 'wrk_' pour identifier immédiatement un espace de travail
        $this->slugId = $this->generate_ulid_prefixed('wrk_');

        $this->members = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    /**
     * Méthode utilitaire vitale pour l'intégrité de la relation bilatérale
     */
    public function addMember(User $user): void
    {
        if (!$this->members->contains($user)) {
            $this->members->add($user);
            $user->workspace = $this; // On lie l'utilisateur à cet espace
        }
    }
}
