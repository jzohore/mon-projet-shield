<?php

namespace App\Domain\Screening\Entity;

use App\Domain\Screening\Enum\ScreeningStatus;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'screening_audits')]
class ScreeningAudit
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

    #[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'screeningAudits')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public private(set) Workspace $workspace;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'owner')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public private(set) User $owner;

    #[ORM\Column(type: Types::STRING, length: 255)]
    public private(set) string $query;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[ORM\Column(type: Types::JSON)]
    public private(set) array $results;

    #[ORM\Column(type: Types::INTEGER)]
    public private(set) int $totalMatches;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::STRING, enumType: ScreeningStatus::class)]
    public private(set) ScreeningStatus $status;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public private(set) ?string $pdfPath = null;

    /**
     * @param array<int, array<string, mixed>> $results
     */
    private function __construct(
        Workspace $workspace,
        User $owner,
        string $query,
        array $results,
        int $totalMatches
    ) {
        $this->workspace = $workspace;
        $this->owner = $owner;
        $this->query = $query;
        $this->results = $results;
        $this->totalMatches = $totalMatches;
        $this->createdAt = new \DateTimeImmutable();
        $this->status = ScreeningStatus::WAIT;
        $this->slugId = $this->generate_ulid_prefixed('scr_aud_');
    }

    /**
     * @param array<int, array<string, mixed>> $results
     */
    public static function create(Workspace $workspace, User $ower, string $query, array $results, int $totalMatches): self
    {
        return new self($workspace, $ower, $query, $results, $totalMatches);
    }

    public function markAsProcessed(): void
    {
        $this->status = ScreeningStatus::PENDING;
    }

    public function markAsGenerated(string $pdfPath): void
    {
        $this->status = ScreeningStatus::GENERATED;
        $this->pdfPath = $pdfPath;
    }

    public function markAsFailed(): void
    {
        $this->status = ScreeningStatus::FAILED;
    }
}
