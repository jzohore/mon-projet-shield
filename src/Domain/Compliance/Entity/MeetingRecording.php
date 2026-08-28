<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Entity;

use App\Domain\Compliance\Exception\CannotAttachGeminiOutputException;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;

use function Symfony\Component\Clock\now;

use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'compliance_meeting_recording')]
#[ORM\UniqueConstraint(name: 'uniq_session_folder', columns: ['session_id', 'compliance_folder_id'])]
class MeetingRecording
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
     * Stocke la réponse brute de l'IA pour CETTE session spécifiquement,
     * avant toute fusion avec le profil global. Crucial pour l'audit.
     *
     * @var array{
     *     executiveSummary?: string,
     *     riskProfileDetected?: string,
     *     kycUpdates?: array<int, string>,
     *     actionPlan?: array<int, string>
     * }|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    public private(set) ?array $geminiRawOutput = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $recordedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $audioDeletedAt = null;

    private function __construct(
        #[ORM\ManyToOne(targetEntity: ComplianceFolder::class, inversedBy: 'meetingRecordings')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        public private(set) ComplianceFolder $complianceFolder,
        #[ORM\Column(type: Types::STRING, length: 255)]
        public private(set) string $sessionId,
        #[ORM\Column(type: Types::STRING, length: 500)]
        public private(set) string $s3Path,
        #[ORM\Column(type: Types::INTEGER)]
        public private(set) int $durationInSeconds,
    ) {
        $this->id = Uuid::v7();
        $this->recordedAt = now();
        $this->slugId = $this->generate_ulid_prefixed('meeting_rec_');
    }

    public static function initialize(
        ComplianceFolder $complianceFolder,
        string $sessionId,
        string $s3Path,
        int $durationInSeconds,
    ): self {
        return new self(
            $complianceFolder,
            $sessionId,
            $s3Path,
            $durationInSeconds
        );
    }

    /**
     * Seule action autorisée après création : l'attachement du rapport IA une fois analysé.
     *
     * @param array{
     *     executiveSummary?: string,
     *     riskProfileDetected?: string,
     *     kycUpdates?: array<int, string>,
     *     actionPlan?: array<int, string>
     * } $output
     *
     * @throws CannotAttachGeminiOutputException
     */
    public function attachGeminiOutput(array $output): void
    {
        if (null !== $this->geminiRawOutput) {
            throw CannotAttachGeminiOutputException::forMeeting();
        }

        $this->geminiRawOutput = $output;
    }

    public function markAudioAsDeleted(): void
    {
        if ($this->audioDeletedAt instanceof \DateTimeImmutable) {
            throw new \DomainException('Le fichier audio a déjà été supprimé.');
        }

        $this->audioDeletedAt = now()->setTimezone(new \DateTimeZone('UTC'));
    }

    public function hasAudioBeenDeleted(): bool
    {
        return $this->audioDeletedAt instanceof \DateTimeImmutable;
    }
}
