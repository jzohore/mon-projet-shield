<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Application\Compliance\UseCase\ComplianceFolder\ComplianceFolderShowAssembler;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Event\DerSentEvent;
use App\Domain\Compliance\Exception\DerCannotBeSentException;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Infrastructure\Compliance\Message\SendDerSignatureMessage;
use App\Infrastructure\DocuSeal\DocuSealClient;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

readonly class SendDerToClientUseCase
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private MessageBusInterface $messageBus,
        private DocuSealClient $docuSealClient,
        private CurrentUserProvider $currentUserProvider,
        private ComplianceFolderRepositoryInterface $folderRepository,
        private ComplianceFolderShowAssembler $complianceFolderShowAssembler,
        private ComplianceDocumentRepositoryInterface $complianceDocumentRepository,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(ComplianceFolder $complianceFolder): void
    {
        // 🛡️ Guard : Garantir l'intégrité de l'agrégat racine
        Assert::notNull($complianceFolder->id, 'Le dossier de conformité ne possède pas d\'ID.');

        $folderAssembler = $this->complianceFolderShowAssembler->assemble($complianceFolder);
        Assert::notNull($folderAssembler, 'Impossible d\'assembler les données du dossier de conformité.');

        // 🛡️ Guard : Validation stricte des données d'entrée pour DocuSeal
        $clientEmail = $folderAssembler->contactEmail;
        Assert::stringNotEmpty($clientEmail, 'L\'email du client est obligatoire pour procéder à la signature.');

        $clientName = trim($folderAssembler->contactFirstName . ' ' . $folderAssembler->contactLastName);
        Assert::stringNotEmpty($clientName, 'Le nom complet du client est obligatoire.');

        // 🚀 Le Piège Métier : Gestion du contexte dual (CGP connecté vs Flash Onboarding public)
        $currentUser = $this->currentUserProvider->getUser();
        $triggeredByUserId = $currentUser?->id?->toString() ?? 'SYSTEM_FLASH_ONBOARDING';

        // 🛡️ Guard : charger le document ET vérifier l'idempotence AVANT tout appel
        // à DocuSeal — un double-clic ne doit pas créer deux soumissions.
        $document = $this->complianceDocumentRepository->findDerByFolder(folder: $complianceFolder);
        Assert::isInstanceOf($document, ComplianceDocument::class, 'Le document DER est introuvable pour ce dossier.');

        if (!$document->canRequestSignature()) {
            throw DerCannotBeSentException::alreadySent();
        }

        $result = $this->docuSealClient->createSignatureRequest(
            $clientEmail,
            $clientName,
        );

        $complianceFolder->markAsDerSent();

        // Vérifier le retour de l'API externe
        Assert::stringNotEmpty($result['id'] ?? '', 'DocuSeal n\'a pas renvoyé d\'ID de soumission.');
        Assert::stringNotEmpty($result['url'] ?? '', 'DocuSeal n\'a pas renvoyé d\'URL de signature.');

        $document->markAsSentForSignature((int) $result['id'], $result['url']);

        $this->transactionManager->transactional(function () use ($document, $complianceFolder): void {
            $this->complianceDocumentRepository->save($document);
            $this->folderRepository->save($complianceFolder);
        });

        $folderId = $complianceFolder->id;
        Assert::notNull($folderId, 'Impossible d\'envoyer le DER : le dossier de conformité n\'a pas d\'identifiant valide.');

        $this->eventDispatcher->dispatch(
            new DerSentEvent(
                folderId: $folderId->toString(),
                triggeredByUserId: $triggeredByUserId
            ),
        );

        // 5. Envoi du mail en asynchrone (via notre propre système)
        $this->messageBus->dispatch(
            new SendDerSignatureMessage(
                clientEmail: $clientEmail,
                clientName: $clientName,
                signatureUrl: $result['url']
            )
        );
    }
}
