<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Handler;

use App\Application\Compliance\UseCase\ComplianceFolder\ComplianceFolderShowAssembler;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Infrastructure\Compliance\Message\DispatchNotifyDerSigned;
use App\Infrastructure\Notification\Email\Compliance\DispatchDerSignedWorkspaceEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
readonly class SendWorkspaceDerNotificationHandler
{
    public function __construct(
        private ComplianceFolderRepositoryInterface $complianceFolderRepository,
        private ComplianceFolderShowAssembler $complianceFolderShowAssembler,
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(DispatchNotifyDerSigned $message): void
    {
        $folderUuid = Uuid::fromString($message->getFolderId());
        $folder = $this->complianceFolderRepository->findById($folderUuid);
        $folderResponse = $this->complianceFolderShowAssembler->assemble($folder);

        $email = new DispatchDerSignedWorkspaceEmail(
            email: $message->getOwnerEmail(),
            clientName: $folderResponse->contactName,
            folderUrl: $message->getFolderUrl(),
            folderRef: $folderResponse->reference,
            workspace_name: $folderResponse->workspaceName,
            signedAt: $message->getSignedAt(),
        );

        $this->mailer->send($email);
    }
}
