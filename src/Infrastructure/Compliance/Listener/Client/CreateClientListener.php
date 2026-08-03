<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener\Client;

use App\Application\Compliance\UseCase\ComplianceFolder\ComplianceFolderShowAssembler;
use App\Application\User\UseCase\Client\AttachExistingClientUseCase;
use App\Application\User\UseCase\Client\CreateClientUseCase;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Event\DerSignedEvent;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\User\Entity\Client;
use App\Domain\User\Exception\ClientNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class CreateClientListener
{
    public function __construct(
        private ComplianceDocumentRepositoryInterface $documentRepository,
        private ComplianceFolderRepositoryInterface $folderRepository,
        private ComplianceFolderShowAssembler $assembler,
        private AttachExistingClientUseCase $attachExistingClientUseCase,
        private CreateClientUseCase $createClientUseCase,
        private LoggerInterface $logger,
    ) {
    }

    // 🪄 Un Listener ne retourne rien (void), c'est une règle d'or de l'Event-Driven Design
    public function __invoke(DerSignedEvent $event): void
    {
        $document = $this->documentRepository->findBySubmissionId($event->getSubmissionId());

        if (!$document instanceof ComplianceDocument) {
            $this->logger->warning('DerSignedEvent: Document introuvable', [
                'submissionId' => $event->getSubmissionId(),
            ]);

            return;
        }

        $folder = $document->folder;
        $workspace = $folder->workspace;

        $folderResponse = $this->assembler->assemble($folder);

        try {
            // 🛡️ 1. Guard Clauses (Type Narrowing pour PHPStan)
            // On certifie au compilateur (et au métier) que ces champs sont obligatoires à ce stade
            Assert::stringNotEmpty($folderResponse->contactEmail, 'Email du contact manquant dans le DTO.');
            Assert::stringNotEmpty($folderResponse->contactFirstName, 'Prénom du contact manquant.');
            Assert::stringNotEmpty($folderResponse->contactLastName, 'Nom du contact manquant.');
            Assert::notNull($workspace->slugId, 'Le SlugId du Workspace est manquant.');

            // 2. Logique d'aiguillage
            try {
                $client = ($this->attachExistingClientUseCase)(
                    $folderResponse->contactEmail,
                    $workspace->slugId
                );
            } catch (ClientNotFoundException) {
                $client = ($this->createClientUseCase)(
                    $folderResponse->contactEmail,
                    $folderResponse->contactFirstName,
                    $folderResponse->contactLastName,
                    $workspace->slugId
                );
            }

            // 3. Sécurité supplémentaire (au cas où le UseCase retournerait autre chose)
            Assert::isInstanceOf($client, Client::class, 'Le UseCase n\'a pas retourné une entité Client valide.');

            // 4. L'attachement au dossier de conformité
            $folder->attachClient($client);
            $this->folderRepository->save($folder);

            $this->logger->info('Dossier de conformité lié au compte client final.', [
                'folder_id' => (string) $folder->id,
                'client_email' => $client->email,
            ]);
        } catch (\Exception $e) {
            $this->logger->critical('Erreur lors de la création/liaison du client', [
                'submissionId' => $event->getSubmissionId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
