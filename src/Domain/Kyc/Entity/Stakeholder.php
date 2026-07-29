<?php

declare(strict_types=1);

namespace App\Domain\Kyc\Entity;

use App\Domain\Kyc\Enum\StakeholderRole;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'stake_holders')]
class Stakeholder
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

    #[ORM\Column(type: 'float', nullable: true)]
    public private(set) ?float $ownershipPercentage = null;

    public bool $isUbo {
        get {
            // Règle 1 : Il a plus de 25% des parts
            if ($this->ownershipPercentage > 25.0) {
                return true;
            }

            // Règle 2 : Il a été explicitement flaggé comme UBO (via le rôle)
            if (StakeholderRole::BENEFICIAL_OWNER === $this->role) {
                return true;
            }

            return false;
        }
    }

    private function __construct(#[ORM\ManyToOne(targetEntity: KycFolder::class, inversedBy: 'stakeholders')]
        #[ORM\JoinColumn(nullable: false)]
        public private(set) KycFolder $folder, #[ORM\Column(length: 100)]
        public private(set) string $firstName, #[ORM\Column(length: 100)]
        public private(set) string $lastName, #[ORM\Column(type: 'string', enumType: StakeholderRole::class)]
        public private(set) StakeholderRole $role)
    {
        $this->slugId = $this->generate_ulid_prefixed('stake_');
    }

    /**
     * 🪄 Named Constructor : Créer un Bénéficiaire Effectif.
     */
    public static function createBeneficialOwner(KycFolder $folder, string $firstName, string $lastName, StakeholderRole $role, ?float $percentage = null): self
    {
        $stakeholder = new self($folder, $firstName, $lastName, $role);
        $stakeholder->ownershipPercentage = $percentage;

        return $stakeholder;
    }

    /**
     * 🪄 Named Constructor : Créer un Dirigeant simple.
     */
    public static function createDirector(KycFolder $folder, string $firstName, string $lastName): self
    {
        return new self($folder, $firstName, $lastName, StakeholderRole::DIRECTOR);
    }

    public function updatePercentage(int $percentage): void
    {
        $this->ownershipPercentage = $percentage;
    }
}
