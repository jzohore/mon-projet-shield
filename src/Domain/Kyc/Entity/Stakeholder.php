<?php

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

    #[ORM\ManyToOne(targetEntity: KycFolder::class, inversedBy: 'stakeholders')]
    #[ORM\JoinColumn(nullable: false)]
    public private(set) KycFolder $folder;

    #[ORM\Column(length: 100)]
    public private(set) string $firstName;

    #[ORM\Column(length: 100)]
    public private(set) string $lastName;

    #[ORM\Column(type: 'string', enumType: StakeholderRole::class)]
    public private(set) StakeholderRole $role;

    #[ORM\Column(type: 'float', nullable: true)]
    public private(set) ?float $ownershipPercentage = null;

    private function __construct(KycFolder $folder, string $firstName, string $lastName, StakeholderRole $role)
    {
        $this->folder = $folder;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->role = $role;
        $this->generate_ulid_prefixed('stake_');
    }

    /**
     * 🪄 Named Constructor : Créer un Bénéficiaire Effectif
     */
    public static function createBeneficialOwner(KycFolder $folder, string $firstName, string $lastName, float $percentage): self
    {
        $stakeholder = new self($folder, $firstName, $lastName, StakeholderRole::BENEFICIAL_OWNER);
        $stakeholder->ownershipPercentage = $percentage;

        return $stakeholder;
    }

    /**
     * 🪄 Named Constructor : Créer un Dirigeant simple
     */
    public static function createDirector(KycFolder $folder, string $firstName, string $lastName): self
    {
        return new self($folder, $firstName, $lastName, StakeholderRole::DIRECTOR);
    }
}
