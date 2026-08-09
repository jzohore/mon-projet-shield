<?php

declare(strict_types=1);

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

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public private(set) ?string $referenceId;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $createdAt;

    public function __construct(#[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'walletTransactions')]
        #[ORM\JoinColumn(nullable: false)]
        public private(set) Workspace $workspace, #[ORM\Column(type: Types::INTEGER)]
        public private(set) int $amount, #[ORM\Column(type: Types::STRING, nullable: true)]
        public private(set) ?string $type, #[ORM\Column(type: Types::STRING, nullable: true)]
        public private(set) ?string $action = null, #[ORM\Column(type: Types::STRING, nullable: true)]
        public private(set) ?string $invoiceUrl = null)
    {
        $this->referenceId = 'WT' . mt_rand(100000, 999999);
        $this->createdAt = new \DateTimeImmutable();
    }
}
