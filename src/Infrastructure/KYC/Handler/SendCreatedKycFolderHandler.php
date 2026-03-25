<?php

namespace App\Infrastructure\KYC\Handler;

use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use App\Infrastructure\KYC\Message\SendCreatedKycFolderMessage;
use App\Infrastructure\Notification\Email\Kyc\DispatchKycRequestEmail;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final readonly class SendCreatedKycFolderHandler
{
    public function __construct(
        private KycFolderRepositoryInterface $kycFolderRepository,
        private LoggerInterface $logger,
        private UrlGeneratorInterface $router,
        private MailerInterface $mailer,
    ) {}

    public function __invoke(SendCreatedKycFolderMessage $message): void
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

        $url = $this->router->generate('portal_kyc_confirm_token', [
            'token' => $kycFolder->shareToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = new DispatchKycRequestEmail(
            recipientEmail: $email,
            recipientFullName: $recipientFullName,
            workspaceName: $workspaceName,
            folderReference: $folderReference,
            actionUrl: $url,
        );
        $this->mailer->send($email);
    }
}
