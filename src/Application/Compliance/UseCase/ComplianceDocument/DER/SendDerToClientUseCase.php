<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Event\DerPdfGeneratedEvent;
use App\Domain\Compliance\Event\DerSentEvent;
use App\Domain\Compliance\Exception\DerCannotBeSentException;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\Kyc\Enum\DocumentStatus;
use App\Domain\Workspace\Service\CurrentUserProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

/**
 * Enregistre la demande de transmission du DER au client. Le lien d'accusé de
 * réception est envoyé par {@see \App\Infrastructure\Compliance\Listener\DER\SendDerAcknowledgementLinkListener}
 * dès que le PDF est prêt (ici s'il l'est déjà, sinon à la fin de la génération).
 */
readonly class SendDerToClientUseCase
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private CurrentUserProvider $currentUserProvider,
        private ComplianceFolderRepositoryInterface $folderRepository,
        private ComplianceDocumentRepositoryInterface $complianceDocumentRepository,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(ComplianceFolder $complianceFolder): void
    {
        $folderId = $complianceFolder->id;
        Assert::notNull($folderId, 'Le dossier de conformité ne possède pas d\'ID.');

        $document = $this->complianceDocumentRepository->findDerByFolder($complianceFolder);
        Assert::isInstanceOf($document, ComplianceDocument::class, 'Le document DER est introuvable pour ce dossier.');

        if ($document->hasAcknowledgementInForce()) {
            throw DerCannotBeSentException::alreadySent();
        }

        // Contexte dual : CGP connecté OU parcours flash public (aucun utilisateur).
        $triggeredByUserId = $this->currentUserProvider->isAuthenticated()
            ? ($this->currentUserProvider->getUser()->id?->toString() ?? 'SYSTEM_FLASH_ONBOARDING')
            : 'SYSTEM_FLASH_ONBOARDING';

        $document->requestAcknowledgementSend();
        $complianceFolder->markAsDerSent();

        $this->transactionManager->transactional(function () use ($document, $complianceFolder): void {
            $this->complianceDocumentRepository->save($document);
            $this->folderRepository->save($complianceFolder);
        });

        $this->eventDispatcher->dispatch(new DerSentEvent(
            folderId: $folderId->toString(),
            triggeredByUserId: $triggeredByUserId,
        ));

        // Si le PDF est déjà généré, le lien peut partir immédiatement.
        if (DocumentStatus::GENERATED === $document->status && $document->id instanceof \Symfony\Component\Uid\Uuid) {
            $this->eventDispatcher->dispatch(new DerPdfGeneratedEvent($document->id->toString()));
        }
    }
}
