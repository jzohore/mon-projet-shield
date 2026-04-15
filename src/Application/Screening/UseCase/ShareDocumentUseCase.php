<?php

namespace App\Application\Screening\UseCase;

use App\Domain\Screening\Event\DocumentSharedEvent;
use App\Domain\Screening\Repository\ScreeningAuditRepositoryInterface;
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
        private ScreeningAuditRepositoryInterface $screeningAuditRepository,
    ) {}

    /**
     * @param array<int, string> $selectedEmails
     *
     * @throws ExceptionInterface
     */
    public function __invoke(array $selectedEmails, string $shareDocumentId, string $senderSlugId): void
    {
        Assert::notEmpty($selectedEmails, 'Aucun destinataire sélectionné.');
        $audit = $this->screeningAuditRepository->findOneBySlug($shareDocumentId);
        Assert::notNull($audit);
        Assert::notNull($audit->workspace->slugId);
        Assert::notNull($audit->owner->email);

        foreach ($selectedEmails as $email) {
            $this->messageBus->dispatch(new ShareDocumentMessage(
                recipientEmail: $email,
                auditSlugId: $shareDocumentId,
                senderSlugId: $senderSlugId,
            ));
        }

        $this->eventDispatcher->dispatch(new DocumentSharedEvent(
            auditId: (string) $audit->id,
            workspaceSlugId: $audit->workspace->slugId,
            userEmail: $audit->owner->email,
            recipients: $selectedEmails,
        ));
    }
}
