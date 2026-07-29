<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Handler;

use App\Application\Compliance\UseCase\ComplianceFolder\ComplianceFolderShowAssembler;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Infrastructure\Compliance\Message\SendClientDerConfirmationMessage;
use App\Infrastructure\Notification\Email\Compliance\DerSignedConfirmationClientEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
readonly class SendClientDerConfirmationHandler
{
    public function __construct(
        private ComplianceFolderRepositoryInterface $complianceFolderRepository,
        private ComplianceFolderShowAssembler $complianceFolderShowAssembler,
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(SendClientDerConfirmationMessage $message): void
    {
        $folderUuid = Uuid::fromString($message->getFolderId());
        $folder = $this->complianceFolderRepository->findById($folderUuid);
        $folderResponse = $this->complianceFolderShowAssembler->assemble($folder);

        Assert::notNull($folderResponse->contactEmail);
        $email = new DerSignedConfirmationClientEmail(
            email: $folderResponse->contactEmail,
            clientName: $folderResponse->contactName,
            loginPageUrl: $message->getLoginPageUrl(),
            workspace_name: $folderResponse->workspaceName,
        );

        $this->mailer->send($email);
    }
}
