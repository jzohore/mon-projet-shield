<?php

namespace App\Infrastructure\Screening\Handler;

use App\Domain\Screening\Repository\ScreeningAuditRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Notification\Email\Documents\ShareReportEmail;
use App\Infrastructure\Screening\Message\ShareDocumentMessage;
use App\Infrastructure\Shared\Twig\S3UrlExtension;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
readonly class ShareDocumentMessageHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private ScreeningAuditRepositoryInterface $screeningAuditRepository,
        private UserRepositoryInterface $userRepository,
        private S3UrlExtension $s3UrlExtension,
    ) {}

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(ShareDocumentMessage $message): void
    {
        $audit = $this->screeningAuditRepository->findOneBySlug($message->auditSlugId);
        $sender = $this->userRepository->findBySlug($message->senderSlugId);

        Assert::notNull($audit);
        Assert::notNull($sender);
        Assert::notNull($audit->pdfPath);

        $actionUrl = $this->s3UrlExtension->generateUrl($audit->pdfPath);

        $email = new ShareReportEmail(
            recipientEmail: $message->recipientEmail,
            senderFullName: $sender->getFullName(),
            subjectName: $audit->query,
            actionUrl: $actionUrl
        );

        $this->mailer->send($email);
    }
}
