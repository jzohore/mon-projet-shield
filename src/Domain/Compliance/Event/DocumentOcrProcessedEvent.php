<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Event;

use App\Domain\Compliance\Entity\ComplianceDocument;

/**
 * Émis quand l'analyse automatique d'un document (OCR + contrôles) est terminée.
 * L'analyse ne décide de rien : elle produit des données extraites et, le cas
 * échéant, des points de vigilance à trancher par le CGP.
 */
final readonly class DocumentOcrProcessedEvent
{
    /**
     * @param list<string> $findings
     */
    public function __construct(
        public ComplianceDocument $document,
        public array $findings,
        public bool $extractionSucceeded,
    ) {
    }
}
