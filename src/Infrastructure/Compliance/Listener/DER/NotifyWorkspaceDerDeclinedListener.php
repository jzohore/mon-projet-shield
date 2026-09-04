<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener\DER;

use App\Domain\Compliance\Entity\IndividualFolder;
use App\Domain\Compliance\Event\DerDeclinedEvent;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Infrastructure\Compliance\Message\DispatchNotifyDerRejected;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

/**
 * Prévient les administrateurs du cabinet qu'un client a refusé de reconnaître
 * le DER.
 */
#[AsEventListener]
readonly class NotifyWorkspaceDerDeclinedListener
{
    public function __construct(
        private ComplianceDocumentRepositoryInterface $complianceDocumentRepository,
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private MessageBusInterface $messageBus,
        private UrlGeneratorInterface $urlGenerator,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(DerDeclinedEvent $event): void
    {
        $document = $this->complianceDocumentRepository->findById($event->getDocumentId());
        Assert::notNull($document);

        $folder = $document->folder;
        $folderId = $folder->id;
        Assert::notNull($folderId);

        $admins = $this->workspaceMemberRepository->findMembersAdmin($folder->workspace);
        if ([] === $admins) {
            $this->logger->warning('Refus de DER : aucun administrateur à notifier.', ['folder_slug_id' => $folder->slugId]);

            return;
        }

        $url = $this->urlGenerator->generate('app_compliance_method_new', [
            'type' => $folder instanceof IndividualFolder ? 'individual' : 'business',
            'method' => 'request',
            'slugId' => $folder->slugId,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        foreach ($admins as $member) {
            $this->messageBus->dispatch(new DispatchNotifyDerRejected(
                folderUrl: $url,
                folderId: $folderId->toString(),
                rejectedAt: $this->clock->now()->format('d-m-Y'),
                ownerEmail: $member->email,
                declineReason: $event->getReason(),
            ));
        }
    }
}
