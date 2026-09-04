<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Enum\DocumentType;
use App\Domain\Compliance\Exception\AcknowledgementLinkException;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;

/**
 * Résout (lecture seule) le DER ciblé par un lien d'accusé de réception, pour
 * l'affichage de la page publique. L'écriture ({@see AcknowledgeDerUseCase})
 * refait sa propre résolution + ses propres gardes.
 */
readonly class ResolveDerAcknowledgementLinkUseCase
{
    private const string TOKEN_PATTERN = '/^[0-9a-f]{64}$/';

    public function __construct(
        private ComplianceDocumentRepositoryInterface $documentRepository,
    ) {
    }

    /**
     * @throws AcknowledgementLinkException
     */
    public function __invoke(string $token): ComplianceDocument
    {
        if (1 !== preg_match(self::TOKEN_PATTERN, $token)) {
            throw AcknowledgementLinkException::invalid();
        }

        $document = $this->documentRepository->findOneByAcknowledgementTokenHash(hash('sha256', $token));

        if (!$document instanceof ComplianceDocument || DocumentType::DER !== $document->type) {
            throw AcknowledgementLinkException::invalid();
        }

        if ($document->isAcknowledgementTokenExpired()) {
            throw AcknowledgementLinkException::expired();
        }

        return $document;
    }
}
