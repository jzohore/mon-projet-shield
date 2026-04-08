<?php

namespace App\Domain\Wallet\Entity;

use App\Domain\Workspace\Entity\Workspace;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'wallet_transactions')]
class WalletTransaction
{
    use GenerateSlugPrefixedTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null {
        get => $this->id;
    }

    #[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'walletTransactions')]
    #[ORM\JoinColumn(nullable: false)]
    public private(set) Workspace $workspace;

    #[ORM\Column(type: Types::INTEGER)]
    public private(set) int $amount = 0;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public private(set) ?string $type = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public private(set) ?string $referenceId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $createdAt = null;

    public function __construct(Workspace $workspace, int $amount, string $type, ?string $referenceId = null)
    {
        $this->workspace = $workspace;
        $this->amount = $amount;
        $this->type = $type;
        $this->referenceId = $referenceId;
        $this->createdAt = new \DateTimeImmutable();
    }
}
