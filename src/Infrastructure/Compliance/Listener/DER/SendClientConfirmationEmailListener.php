<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener\DER;

use App\Domain\Compliance\Event\DerSignedEvent;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Infrastructure\Compliance\Message\SendClientDerConfirmationMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class SendClientConfirmationEmailListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private UrlGeneratorInterface $urlGenerator,
        private ComplianceDocumentRepositoryInterface $complianceDocumentRepository,
    ) {
    }

    public function __invoke(DerSignedEvent $event): void
    {
        $document = $this->complianceDocumentRepository->findBySubmissionId($event->getSubmissionId());

        Assert::notNull($document);

        $folder = $document->folder;

        Assert::notNull($folder);
        Assert::notNull($document->id);

        $url = $this->urlGenerator->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL);

        Assert::notNull($folder->id);
        $this->messageBus->dispatch(new SendClientDerConfirmationMessage(
            loginPageUrl: $url,
            folderId: $folder->id->toString(),
        ));
    }
}
