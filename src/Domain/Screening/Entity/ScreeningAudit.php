<?php

declare(strict_types=1);

namespace App\Domain\Screening\Entity;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Screening\Enum\ScreeningStatus;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;

use function Symfony\Component\Clock\now;

use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'screening_audits')]
class ScreeningAudit
{
    use GenerateSlugPrefixedTrait;

    /**
     * Délai au-delà duquel les données brutes des correspondances (`raw_data`,
     * dump Open Sanctions contenant des PII de tiers homonymes) sont retirées.
     * On conserve le résumé exploitable + une empreinte d'intégrité.
     */
    public const int RESULTS_RETENTION_DAYS = 30;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null {
        get => $this->id;
    }

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public private(set) string $slugId;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::STRING, enumType: ScreeningStatus::class)]
    public private(set) ScreeningStatus $status = ScreeningStatus::WAIT;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public private(set) ?string $pdfPath = null;

    /** Empreinte SHA-256 des résultats d'origine, posée au moment de leur minimisation. */
    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    public private(set) ?string $resultsDigest = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $resultsMinimizedAt = null;

    /**
     * @param array<int, array<string, mixed>> $results
     *
     * @throws \Exception
     */
    private function __construct(
        #[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'screeningAudits')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        public private(set) Workspace $workspace,
        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'screeningAudits')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        public private(set) User $owner,
        #[ORM\Column(type: Types::STRING, length: 255)]
        public private(set) string $query,
        #[ORM\Column(type: Types::JSON)]
        public private(set) array $results,
        #[ORM\Column(type: Types::INTEGER)]
        public private(set) int $totalMatches,
        /**
         * Dossier de conformité à l'origine du screening, le cas échéant. Nul
         * pour un screening ad hoc (recherche libre hors dossier). Sans ce
         * rattachement, impossible de prouver « ce screening a été fait pour ce
         * dossier, sur cette personne ».
         */
        #[ORM\ManyToOne(targetEntity: ComplianceFolder::class)]
        #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
        public private(set) ?ComplianceFolder $complianceFolder = null,
    ) {
        $this->createdAt = now();
        $this->slugId = $this->generate_ulid_prefixed('scr_aud_');
    }

    /**
     * @param array<int, array<string, mixed>> $results
     *
     * @throws \Exception
     */
    public static function create(Workspace $workspace, User $ower, string $query, array $results, int $totalMatches, ?ComplianceFolder $complianceFolder = null): self
    {
        return new self($workspace, $ower, $query, $results, $totalMatches, $complianceFolder);
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

    /**
     * Minimisation RGPD : retire le `raw_data` de chaque correspondance (dump
     * Open Sanctions complet, PII de tiers) tout en conservant le résumé
     * exploitable et une empreinte d'intégrité des résultats d'origine.
     * Idempotent.
     */
    public function minimizeResults(): void
    {
        if ($this->resultsMinimizedAt instanceof \DateTimeImmutable) {
            return;
        }

        $this->resultsDigest = hash('sha256', json_encode($this->results, \JSON_THROW_ON_ERROR));

        $this->results = array_map(
            static function (array $alert): array {
                unset($alert['raw_data']);

                return $alert;
            },
            $this->results,
        );

        $this->resultsMinimizedAt = now();
    }

    public function areResultsMinimized(): bool
    {
        return $this->resultsMinimizedAt instanceof \DateTimeImmutable;
    }
}
