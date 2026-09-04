<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener\DER;

use App\Application\Compliance\UseCase\ComplianceFolder\ComplianceFolderShowAssembler;
use App\Domain\Compliance\Event\DerPdfGeneratedEvent;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Database\TransactionManagerInterface;
use App\Infrastructure\Compliance\Message\SendDerSignatureMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

/**
 * Envoie au client le lien nominatif d'accusé de réception du DER, une fois le
 * PDF prêt et si le CGP a demandé la transmission. Le jeton en clair est émis
 * ici (jamais persisté ni mis en file), l'e-mail part par Messenger.
 * Idempotent : rejouable sans renvoi ni double jeton.
 */
#[AsEventListener]
readonly class SendDerAcknowledgementLinkListener
{
    public function __construct(
        private ComplianceDocumentRepositoryInterface $documentRepository,
        private ComplianceFolderShowAssembler $folderShowAssembler,
        private UrlGeneratorInterface $urlGenerator,
        private MessageBusInterface $messageBus,
        private TransactionManagerInterface $transactionManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(DerPdfGeneratedEvent $event): void
    {
        $document = $this->documentRepository->findById($event->getDocumentId());
        Assert::notNull($document, 'DER introuvable à l\'envoi du lien d\'accusé de réception.');

        if (!$document->isAcknowledgementSendRequested()
            || $document->isAcknowledgementLinkSent()
            || $document->hasAcknowledgementInForce()
        ) {
            return;
        }

        $dto = $this->folderShowAssembler->assemble($document->folder);
        $clientEmail = $dto->contactEmail ?? '';

        if ('' === $clientEmail) {
            $this->logger->warning('Envoi du lien d\'accusé de réception impossible : e-mail client absent.', [
                'document_id' => $event->getDocumentId(),
            ]);

            return;
        }

        $clearToken = $document->issueAcknowledgementToken();
        $url = $this->urlGenerator->generate(
            'app_der_acknowledge',
            ['token' => $clearToken],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $document->markAcknowledgementLinkSent();
        $this->transactionManager->transactional(fn () => $this->documentRepository->save($document));

        $this->messageBus->dispatch(new SendDerSignatureMessage(
            clientEmail: $clientEmail,
            clientName: $dto->contactName,
            signatureUrl: $url,
        ));
    }
}
