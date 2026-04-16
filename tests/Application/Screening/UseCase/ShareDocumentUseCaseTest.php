<?php

declare(strict_types=1);

namespace App\Tests\Application\Screening\UseCase;

use App\Application\Screening\UseCase\ShareDocumentUseCase;
use App\Domain\Screening\Entity\ScreeningAudit;
use App\Domain\Screening\Event\DocumentSharedEvent;
use App\Domain\Screening\Repository\ScreeningAuditRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Enum\Industry;
use App\Infrastructure\Screening\Message\ShareDocumentMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class ShareDocumentUseCaseTest extends TestCase
{
    #[Test]
    public function it_dispatches_a_message_for_each_recipient_and_emits_a_document_shared_event(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $screeningAuditRepository = $this->createMock(ScreeningAuditRepositoryInterface::class);

        $audit = $this->createScreeningAudit();

        $selectedEmails = [
            'alice@example.com',
            'bob@example.com',
        ];

        $screeningAuditRepository->expects(self::once())
            ->method('findOneBySlug')
            ->with($audit->slugId)
            ->willReturn($audit);

        $sentMessages = [];

        $messageBus->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(static function (ShareDocumentMessage $message) use (&$sentMessages): Envelope {
                $sentMessages[] = $message;

                return new Envelope($message);
            });

        $eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (object $event) use ($selectedEmails, $audit): bool {
                return $event instanceof DocumentSharedEvent
                    && $event->auditId === (string) $audit->id
                    && $event->workspaceSlugId === $audit->workspace->slugId
                    && $event->userEmail === $audit->owner->email
                    && $event->recipients === $selectedEmails;
            }));

        $useCase = new ShareDocumentUseCase(
            $messageBus,
            $eventDispatcher,
            $screeningAuditRepository,
        );

        $useCase($selectedEmails, $audit->slugId, 'usr_sender');

        self::assertCount(2, $sentMessages);
        self::assertSame('alice@example.com', $sentMessages[0]->recipientEmail);
        self::assertSame('bob@example.com', $sentMessages[1]->recipientEmail);
        self::assertSame($audit->slugId, $sentMessages[0]->auditSlugId);
        self::assertSame($audit->slugId, $sentMessages[1]->auditSlugId);
        self::assertSame('usr_sender', $sentMessages[0]->senderSlugId);
        self::assertSame('usr_sender', $sentMessages[1]->senderSlugId);
    }

    private function createScreeningAudit(): ScreeningAudit
    {
        $workspace = Workspace::create(
            name: 'Cabinet Test',
            siret: '12345678901234',
            legalName: 'Cabinet Test SARL',
            address: '1 rue de Paris',
            industry: Industry::LAWYER
        );

        $owner = User::create(
            email: 'owner@example.com',
            firstName: 'Owner',
            lastName: 'User',
            isVerified: true,
            roles: ['ROLE_USER'],
            isActif: true,
        );

        return ScreeningAudit::create(
            workspace: $workspace,
            ower: $owner,
            query: 'John Doe',
            results: [
                ['name' => 'John Doe', 'score' => 95],
            ],
            totalMatches: 1,
        );
    }
}
