<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener;

use App\Domain\Compliance\Event\DocumentReceivedLocalEvent;
use App\Infrastructure\KYC\Message\ProcessAndStoreKycDocumentMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class DocumentReceivedListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(DocumentReceivedLocalEvent $event): void
    {
        Assert::notNull($event->complianceDocument->id);
        Assert::notNull($event->complianceFolder->id);

        $this->messageBus->dispatch(new ProcessAndStoreKycDocumentMessage(
            documentId: $event->complianceDocument->id->toString(),
            folderId: $event->complianceFolder->id->toString(),
            localTempPath: $event->localTempPath,
            mimeType: $event->mimeType,
            originalName: $event->originalName,
            size: $event->size,
            oldStoragePath: $event->oldStoragePath
        ));
    }
}
