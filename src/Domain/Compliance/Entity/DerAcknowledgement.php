<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Entity;

use App\Domain\Compliance\ValueObject\DerStatement;
use App\Domain\User\Entity\User;
use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;

use function Symfony\Component\Clock\now;

use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

/**
 * Preuve figée qu'un client a accusé réception d'un DER précis, à une date
 * donnée. Le DER étant un document d'information précontractuelle (et non un
 * contrat), sa « signature » vaut accusé de réception (niveau eIDAS simple).
 *
 * Immuable par construction : les octets du PDF servi sont figés par leur
 * empreinte SHA-256 et le chemin de stockage est snapshoté — le PDF « courant »
 * du document peut être régénéré, la preuve ne bouge pas. Une correction passe
 * par {@see self::revoke()} + nouvel accusé, jamais par mutation.
 */
#[ORM\Entity]
#[ORM\Table(name: 'compliance_der_acknowledgement')]
#[ORM\UniqueConstraint(
    name: 'uniq_der_ack_document_in_force',
    columns: ['compliance_document_id'],
    options: ['where' => '(revoked_at IS NULL)'],
)]
class DerAcknowledgement
{
    use GenerateSlugPrefixedTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public private(set) ?Uuid $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public private(set) string $slugId;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public private(set) \DateTimeImmutable $acknowledgedAt;

    /** Texte exact de l'attestation cochée, figé pour la preuve. */
    #[ORM\Column(type: Types::TEXT)]
    public private(set) string $statementText;

    #[ORM\Column(type: Types::STRING, length: 20)]
    public private(set) string $statementVersion;

    /** Attestation PDF générée après l'accusé (certificat téléchargeable par le cabinet). */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public private(set) ?string $certificateStoragePath = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    public private(set) ?string $certificateSha256 = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public private(set) ?string $userAgent = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public private(set) ?\DateTimeImmutable $revokedAt = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public private(set) ?string $revokedByName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public private(set) ?string $revokeReason = null;

    private function __construct(
        #[ORM\ManyToOne(targetEntity: ComplianceDocument::class, inversedBy: 'acknowledgements')]
        #[ORM\JoinColumn(name: 'compliance_document_id', nullable: false, onDelete: 'RESTRICT')]
        public private(set) ComplianceDocument $document,
        /**
         * Empreinte SHA-256 des octets du PDF effectivement servis au client au
         * moment de l'accusé. Sert à prouver l'intégrité du document acquitté.
         */
        #[ORM\Column(type: Types::STRING, length: 64)]
        public private(set) string $pdfSha256,
        /**
         * Chemin de stockage du PDF acquitté, figé : {@see GenerateDerPdfHandler}
         * supprime l'ancien PDF à chaque régénération, la preuve doit garder le sien.
         */
        #[ORM\Column(type: Types::STRING, length: 255)]
        public private(set) string $pdfStoragePath,
        /** Nom saisi par le client dans le formulaire d'accusé. */
        #[ORM\Column(type: Types::STRING, length: 255)]
        public private(set) string $declaredName,
        /** Destinataire du DER au moment de l'envoi (copie dénormalisée). */
        #[ORM\Column(type: Types::STRING, length: 180)]
        public private(set) string $recipientEmail,
        DerStatement $statement,
        /**
         * Finalité probatoire (preuve de remise du DER). Donnée personnelle :
         * n'apparaît jamais dans un log applicatif, effaçable avec le dossier.
         */
        #[ORM\Column(type: Types::STRING, length: 45, nullable: true)]
        public private(set) ?string $ipAddress,
        ?string $userAgent,
    ) {
        $this->id = Uuid::v7();
        $this->slugId = $this->generate_ulid_prefixed('der_ack_');
        $this->acknowledgedAt = now();
        $this->statementText = $statement->text;
        $this->statementVersion = $statement->version;
        $this->userAgent = null !== $userAgent ? mb_substr($userAgent, 0, 255) : null;
    }

    public static function record(
        ComplianceDocument $document,
        string $pdfSha256,
        string $pdfStoragePath,
        string $declaredName,
        string $recipientEmail,
        DerStatement $statement,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): self {
        $declaredName = trim($declaredName);

        Assert::stringNotEmpty($declaredName, 'Le nom du client est obligatoire pour accuser réception du DER.');
        Assert::regex($pdfSha256, '/^[0-9a-f]{64}$/', 'Empreinte SHA-256 du PDF invalide.');
        Assert::stringNotEmpty($pdfStoragePath, 'Le chemin du PDF acquitté est obligatoire.');
        Assert::stringNotEmpty($recipientEmail, 'Le destinataire du DER est obligatoire.');

        return new self(
            $document,
            $pdfSha256,
            $pdfStoragePath,
            $declaredName,
            $recipientEmail,
            $statement,
            $ipAddress,
            $userAgent,
        );
    }

    /**
     * Révoque l'accusé : il reste archivé et consultable, mais n'est plus en
     * vigueur (le DER peut alors être régénéré et un nouvel accusé émis).
     * Action irréversible.
     */
    public function revoke(User $revokedBy, string $reason): void
    {
        if ($this->revokedAt instanceof \DateTimeImmutable) {
            throw new \DomainException('Cet accusé de réception a déjà été révoqué.');
        }

        $reason = trim($reason);
        if ('' === $reason) {
            throw new \DomainException('Un motif est obligatoire pour révoquer un accusé de réception de DER.');
        }

        $this->revokedAt = now();
        $this->revokedByName = $revokedBy->getFullName();
        $this->revokeReason = $reason;
    }

    /**
     * Rattache l'attestation PDF générée après coup. Idempotent : une attestation
     * déjà rattachée n'est pas écrasée (empreinte figée).
     */
    public function attachCertificate(string $storagePath, string $sha256): void
    {
        if (null !== $this->certificateStoragePath) {
            return;
        }

        $this->certificateStoragePath = $storagePath;
        $this->certificateSha256 = $sha256;
    }

    public function hasCertificate(): bool
    {
        return null !== $this->certificateStoragePath;
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
     * Vérifie que les octets fournis correspondent au PDF réellement acquitté.
     */
    public function matchesStoredHash(string $currentPdfContents): bool
    {
        return hash_equals($this->pdfSha256, hash('sha256', $currentPdfContents));
    }
}
