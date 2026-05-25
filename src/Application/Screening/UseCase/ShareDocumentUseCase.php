<?php

namespace App\Application\Screening\UseCase;

use App\Domain\Screening\Entity\ScreeningAudit;
use App\Domain\Screening\Event\DocumentSharedEvent;
use App\Infrastructure\Screening\Message\ShareDocumentMessage;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

readonly class ShareDocumentUseCase
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @param array<int, string> $selectedEmails
     *
     * @throws ExceptionInterface
     */
    public function __invoke(array $selectedEmails, ScreeningAudit $audit): void
    {
        Assert::notEmpty($selectedEmails, 'Aucun destinataire sélectionné.');

        Assert::notNull($audit->owner->id);
        Assert::notNull($audit->id);

        foreach ($selectedEmails as $email) {
            $this->messageBus->dispatch(new ShareDocumentMessage(
                recipientEmail: $email,
                auditId: $audit->id->toString(),
                senderId: $audit->owner->id->toString(),
            ));
        }

        $this->eventDispatcher->dispatch(new DocumentSharedEvent(
            audit: $audit,
            workspace: $audit->workspace,
            user: $audit->owner,
            recipients: $selectedEmails,
        ));
    }
}
