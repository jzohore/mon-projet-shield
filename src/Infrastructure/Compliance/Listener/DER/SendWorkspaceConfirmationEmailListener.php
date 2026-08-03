<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener\DER;

use App\Domain\Compliance\Entity\IndividualFolder;
use App\Domain\Compliance\Event\DerSignedEvent;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Infrastructure\Compliance\Message\DispatchNotifyDerSigned;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class SendWorkspaceConfirmationEmailListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private UrlGeneratorInterface $urlGenerator,
        private ComplianceDocumentRepositoryInterface $complianceDocumentRepository,
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(DerSignedEvent $event): void
    {
        $document = $this->complianceDocumentRepository->findBySubmissionId($event->getSubmissionId());

        Assert::notNull($document);

        $folder = $document->folder;

        Assert::notNull($folder);
        Assert::notNull($document->id);

        $membersToNotify = $this->workspaceMemberRepository->findMembersAdmin($folder->workspace);

        if ([] === $membersToNotify) {
            $this->logger->critical('NotifyDerSigned: No owner found for workspace.', [
                'workspace_id' => $folder->workspace->id,
            ]);

            return; // On stoppe le process proprement
        }

        $type = $folder instanceof IndividualFolder
            ? 'individual'
            : 'business';

        $url = $this->urlGenerator->generate('app_compliance_method_new', [
            'type' => $type,
            'method' => 'request',
            'slugId' => $folder->slugId,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        Assert::notNull($folder->id);
        foreach ($membersToNotify as $member) {
            $this->messageBus->dispatch(new DispatchNotifyDerSigned(
                folderUrl: $url,
                folderId: $folder->id->toString(),
                signedAt: $event->getCompletedAt()->format('d-m-Y'),
                ownerEmail: $member->email,
            ));
        }
    }
}
