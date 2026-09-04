<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Event\DerGenerationRequestedEvent;
use App\Domain\Compliance\Exception\Document\DocumentNotFoundException;
use App\Domain\Compliance\Exception\Document\InvalidDocumentFolderException;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Infrastructure\Compliance\Message\GenerateDerPdfMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

readonly class GenerateDerUseCase
{
    public function __construct(
        private ComplianceDocumentRepositoryInterface $repository,
        private ComplianceFolderRepositoryInterface $folderRepository,
        private MessageBusInterface $messageBus,
        private CurrentUserProvider $currentUserProvider,
        private EventDispatcherInterface $eventDispatcher,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(string $documentId, ComplianceFolder $folder): void
    {
        $document = $this->repository->findById($documentId);

        if (!$document instanceof \App\Domain\Compliance\Entity\ComplianceDocument) {
            throw DocumentNotFoundException::withId($documentId);
        }

        if ($document->folder !== $folder) {
            throw InvalidDocumentFolderException::forDocument($documentId, $folder->slugId);
        }

        // Contexte dual : CGP connecté OU parcours flash public (aucun utilisateur).
        $user = null;
        $actorEmail = 'onboarding-flash@kysure.local';
        if ($this->currentUserProvider->isAuthenticated()) {
            $user = $this->currentUserProvider->getUser();
            $actorEmail = $user->email;
        }

        $document->markAsPending($actorEmail);
        // Nouveau cycle : un DER régénéré après révocation ou refus client doit
        // pouvoir repartir en circulation (nouveau lien, nouveau jeton).
        $document->reopenAcknowledgementCircuit();
        $folder->markAsDerGenerated();

        $this->transactionManager->transactional(function () use ($document, $folder): void {
            $this->repository->save($document);
            $this->folderRepository->save($folder);
        });

        $this->messageBus->dispatch(new GenerateDerPdfMessage($documentId, $document->storagePath));

        if ($user instanceof \App\Domain\User\Entity\User) {
            $this->eventDispatcher->dispatch(new DerGenerationRequestedEvent($document, $user));
        }
    }
}
