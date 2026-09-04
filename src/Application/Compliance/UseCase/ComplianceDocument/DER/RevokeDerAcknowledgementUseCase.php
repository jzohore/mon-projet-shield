<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Domain\Compliance\Event\DerAcknowledgementRevokedEvent;
use App\Domain\Compliance\Exception\DerAcknowledgementNotFoundException;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Compliance\Repository\DerAcknowledgementRepositoryInterface;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;

use function Symfony\Component\Clock\now;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Révoque un accusé de réception du DER (action d'un administrateur du cabinet,
 * motif obligatoire). L'accusé reste archivé ; le dossier repasse en refus et un
 * nouveau DER pourra être émis.
 */
readonly class RevokeDerAcknowledgementUseCase
{
    public function __construct(
        private DerAcknowledgementRepositoryInterface $acknowledgementRepository,
        private ComplianceFolderRepositoryInterface $folderRepository,
        private CurrentUserProvider $currentUserProvider,
        private TransactionManagerInterface $transactionManager,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(string $acknowledgementSlugId, string $reason): void
    {
        $acknowledgement = $this->acknowledgementRepository->findBySlugId($acknowledgementSlugId);

        if (!$acknowledgement instanceof \App\Domain\Compliance\Entity\DerAcknowledgement) {
            throw DerAcknowledgementNotFoundException::withSlugId($acknowledgementSlugId);
        }

        $user = $this->currentUserProvider->getUser();
        $reason = trim($reason);

        // Gardes (motif obligatoire, double révocation) portées par l'entité.
        $acknowledgement->revoke($user, $reason);

        $folder = $acknowledgement->document->folder;
        $folder->recordDerAcknowledgementRevoked(now()->format('d/m/y H:i'), $user->getFullName(), $reason);

        $this->transactionManager->transactional(function () use ($acknowledgement, $folder): void {
            $this->acknowledgementRepository->save($acknowledgement, flush: false);
            $this->folderRepository->save($folder);
        });

        $documentId = $acknowledgement->document->id?->toString();
        if (null === $documentId) {
            throw DerAcknowledgementNotFoundException::withSlugId($acknowledgementSlugId);
        }

        $this->eventDispatcher->dispatch(new DerAcknowledgementRevokedEvent(
            documentId: $documentId,
            folderSlugId: $folder->slugId,
            acknowledgementSlugId: $acknowledgement->slugId,
            revokedByName: $user->getFullName(),
            reason: $reason,
        ));
    }
}
