<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Repository;

use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Entity\DerAcknowledgement;
use Symfony\Component\Uid\Uuid;

interface DerAcknowledgementRepositoryInterface
{
    /**
     * Persiste un accusé de réception.
     *
     * Volontairement pas de `remove()` : un accusé est une pièce de preuve, il
     * se révoque ({@see DerAcknowledgement::revoke()}), il ne se supprime pas.
     */
    public function save(DerAcknowledgement $acknowledgement, bool $flush = true): void;

    public function findById(Uuid|string $id): ?DerAcknowledgement;

    public function findBySlugId(string $slugId): ?DerAcknowledgement;

    /**
     * L'accusé actuellement en vigueur (non révoqué) pour ce DER, ou `null`.
     */
    public function findInForceByDocument(ComplianceDocument $document): ?DerAcknowledgement;
}
