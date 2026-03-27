<?php

namespace App\Infrastructure\KYC\Handler;

use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use App\Infrastructure\KYC\Message\SendSubmittedKycFolderMessage;
use App\Infrastructure\Notification\Email\Kyc\ClientKycConfirmationEmail;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final readonly class NotifyClientOfKycSubmission
{
    public function __construct(
        private KycFolderRepositoryInterface $kycFolderRepository,
        private LoggerInterface $logger,
        private MailerInterface $mailer,
    ) {}

    public function __invoke(SendSubmittedKycFolderMessage $message): void
    {
        $slugId = $message->slugId;
        Assert::notNull($slugId);
        $kycFolder = $this->kycFolderRepository->findBySlugId($message->slugId);

        if (!$kycFolder) {
            $this->logger->info('Envoi annulé : l\'kyc folder est introuvable.', [
                'workspaceSlugId' => $kycFolder,
            ]);
            return;
        }

        $email = $kycFolder->contactEmail;
        $recipientFullName = $kycFolder->getFullName();
        $workspaceName = $kycFolder->workspace->name;
        $folderReference = $kycFolder->reference;

        Assert::notNull($email);
        Assert::notNull($workspaceName);

        $email = new ClientKycConfirmationEmail(
            recipientEmail: $email,
            recipientFullName: $recipientFullName,
            workspaceName: $workspaceName,
            folderReference: $folderReference,
        );
        $this->mailer->send($email);
    }
}
