<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Entity;

use App\Domain\User\Entity\User;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;

use function Symfony\Component\Clock\now;

use Symfony\Component\Uid\Uuid;

/**
 * Instantané figé de la synthèse d'entretien consolidée d'un dossier, tel que
 * validé par le CGP responsable.
 *
 * Contrairement au rapport « holistique » qui est recalculé à la volée à partir
 * des {@see MeetingRecording} actifs, cette entité stocke une COPIE gelée du
 * contenu au moment de la validation : elle ne bouge plus, même si un
 * enregistrement source est archivé/supprimé ou si la logique de fusion évolue.
 *
 * La ligne n'existe qu'à partir de la validation : `validatedAt` / `validatedBy`
 * sont donc toujours renseignés. `revokedAt` ne l'est que si le rapport est
 * révoqué par la suite (remplacé par une nouvelle version).
 */
#[ORM\Entity]
#[ORM\Table(name: 'compliance_validated_meeting_report')]
#[ORM\UniqueConstraint(name: 'uniq_folder_report_version', columns: ['compliance_folder_id', 'version'])]
class ValidatedMeetingReport
{
    use GenerateSlugPrefixedTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public private(set) ?Uuid $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public private(set) string $slugId;

    /**
     * Empreinte SHA-256 de `content` au moment de la validation : permet de
     * prouver que le contenu affiché n'a pas été altéré.
     */
    #[ORM\Column(type: Types::STRING, length: 64)]
    public private(set) string $contentHash;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $validatedAt;

    /**
     * Copie dénormalisée du nom du valideur : l'instantané doit rester lisible
     * même si le compte utilisateur est supprimé plus tard.
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    public private(set) string $validatedByName;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $revokedAt = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public private(set) ?string $revokedByName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public private(set) ?string $revokeReason = null;

    /**
     * @param array{
     *     summary: string,
     *     riskProfile: string,
     *     kycUpdates: array<int, array{date: string, items: array<int, string>}>,
     *     actionPlan: array<int, array{date: string, items: array<int, string>}>,
     *     slugId: array<int, string>,
     *     isExplorable?: bool,
     *     isAdjusted?: bool
     * } $content
     * @param list<string> $sourceRecordingSlugs
     */
    private function __construct(
        #[ORM\ManyToOne(targetEntity: ComplianceFolder::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        public private(set) ComplianceFolder $complianceFolder,
        #[ORM\ManyToOne(targetEntity: User::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
        public private(set) User $validatedBy,
        /**
         * Copie intégrale et figée de {@see \App\Application\Compliance\DTO\Response\HolisticMeetingReportDto::toArray()}
         * au moment de la validation (texte éventuellement amendé par le CGP).
         */
        #[ORM\Column(type: Types::JSON)]
        public private(set) array $content,
        /**
         * Les `slugId` des {@see MeetingRecording} inclus dans l'instantané.
         * Traçabilité : sur quelle base le rapport a été validé.
         */
        #[ORM\Column(type: Types::JSON)]
        public private(set) array $sourceRecordingSlugs,
        /**
         * Version incrémentale par dossier. Au plus une version « en vigueur »
         * (non révoquée) à la fois.
         */
        #[ORM\Column(type: Types::INTEGER, options: ['default' => 1])]
        public private(set) int $version,
    ) {
        $this->id = Uuid::v7();
        $this->slugId = $this->generate_ulid_prefixed('meeting_report_');
        $this->contentHash = hash('sha256', json_encode($this->content, \JSON_THROW_ON_ERROR));
        $this->validatedAt = now();
        $this->validatedByName = $validatedBy->getFullName();
    }

    /**
     * Fige la synthèse d'entretien du dossier telle que validée par le CGP.
     *
     * @param array{
     *     summary: string,
     *     riskProfile: string,
     *     kycUpdates: array<int, array{date: string, items: array<int, string>}>,
     *     actionPlan: array<int, array{date: string, items: array<int, string>}>,
     *     slugId: array<int, string>,
     *     isExplorable?: bool,
     *     isAdjusted?: bool
     * } $content
     * @param list<string> $sourceRecordingSlugs
     */
    public static function validate(
        ComplianceFolder $complianceFolder,
        User $validatedBy,
        array $content,
        array $sourceRecordingSlugs,
        int $version = 1,
    ): self {
        return new self(
            $complianceFolder,
            $validatedBy,
            $content,
            $sourceRecordingSlugs,
            $version,
        );
    }

    /**
     * Révoque le rapport : il reste consultable et archivé, mais n'est plus
     * en vigueur (remplacé par une nouvelle version). Action irréversible.
     */
    public function revoke(User $revokedBy, string $reason): void
    {
        if ($this->revokedAt instanceof \DateTimeImmutable) {
            throw new \DomainException('Ce rapport d\'entretien a déjà été révoqué.');
        }

        $reason = trim($reason);
        if ('' === $reason) {
            throw new \DomainException('Un motif est obligatoire pour révoquer un rapport d\'entretien validé.');
        }

        $this->revokedAt = now();
        $this->revokedByName = $revokedBy->getFullName();
        $this->revokeReason = $reason;
    }

    public function isInForce(): bool
    {
        return !$this->revokedAt instanceof \DateTimeImmutable;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt instanceof \DateTimeImmutable;
    }

    /**
     * Vérifie l'intégrité de l'instantané stocké.
     */
    public function matchesStoredHash(): bool
    {
        return hash_equals(
            $this->contentHash,
            hash('sha256', json_encode($this->content, \JSON_THROW_ON_ERROR)),
        );
    }
}
