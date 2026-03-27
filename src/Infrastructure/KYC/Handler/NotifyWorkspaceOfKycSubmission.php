<?php

namespace App\Infrastructure\KYC\Handler;

use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use App\Infrastructure\KYC\Message\SendSubmittedKycFolderMessage;
use App\Infrastructure\Notification\Email\Kyc\WorkspaceKycConfirmationEmail;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final readonly class NotifyWorkspaceOfKycSubmission
{
    public function __construct(
        private KycFolderRepositoryInterface $kycFolderRepository,
        private LoggerInterface $logger,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $router,
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
        $companyName = $kycFolder->companyName;

        Assert::notNull($email);
        Assert::notNull($workspaceName);
        Assert::notNull($companyName);

        $url = $this->router->generate('app_kyc_show', [
            'slugId' => $kycFolder->slugId,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = new WorkspaceKycConfirmationEmail(
            recipientEmail: $email,
            recipientFullName: $recipientFullName,
            workspaceName: $workspaceName,
            companyName: $companyName,
            folderReference: $folderReference,
            reviewUrl: $url,
        );
        $this->mailer->send($email);
    }
}
