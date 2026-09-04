<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Event\DerDeclinedEvent;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Database\TransactionManagerInterface;

use function Symfony\Component\Clock\now;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Le client déclare, depuis la page publique, ne pas reconnaître le DER. Le
 * dossier repasse en refus (procédure d'entrée en relation suspendue).
 */
readonly class DeclineDerUseCase
{
    public function __construct(
        private ComplianceDocumentRepositoryInterface $documentRepository,
        private ComplianceFolderRepositoryInterface $folderRepository,
        private TransactionManagerInterface $transactionManager,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(string $token, ?string $reason): void
    {
        $document = $this->documentRepository->findOneByAcknowledgementTokenHash(hash('sha256', $token));

        if (!$document instanceof ComplianceDocument) {
            throw new \DomainException('Lien invalide.');
        }

        if ($document->hasAcknowledgementInForce()) {
            throw new \DomainException('Ce document a déjà été accusé en réception et ne peut plus être refusé ici.');
        }

        // 🛡️ Idempotence : un refus déjà enregistré n'est pas rejoué.
        if ($document->isDerDeclined()) {
            return;
        }

        $document->markDerDeclined($reason);

        $folder = $document->folder;
        $folder->markAsDerRejected(now()->format('d/m/y H:i'), $reason);

        $this->transactionManager->transactional(function () use ($document, $folder): void {
            $this->documentRepository->save($document, flush: false);
            $this->folderRepository->save($folder);
        });

        $documentId = $document->id?->toString();
        if (null === $documentId) {
            return;
        }

        $this->eventDispatcher->dispatch(new DerDeclinedEvent(
            documentId: $documentId,
            folderSlugId: $folder->slugId,
            reason: $document->derDeclineReason,
        ));
    }
}
