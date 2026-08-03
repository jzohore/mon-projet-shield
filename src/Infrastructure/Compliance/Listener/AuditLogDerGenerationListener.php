<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Compliance\Event\DerGenerationRequestedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class AuditLogDerGenerationListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(DerGenerationRequestedEvent $event): void
    {
        $document = $event->getDocument();
        $folder = $document->folder;
        $workspace = $folder->workspace;
        $user = $event->getUser();

        Assert::notNull($document->id);
        Assert::notNull($user->id);
        Assert::notNull($workspace);

        $audit = AuditLog::initiate(
            eventName: AuditEventType::DER_GENERATED, // 🪄 Changement de type d'événement
            payload: [
                'document_id' => $document->id->toString(),
                'document_reference' => $folder->reference,
                'folder_slug_id' => $folder->slugId,
                'workspace_name' => $workspace->name,
                'actor_name' => $user->getFullName(),
                'actor_email' => $user->email,
                'generated_at' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
            ],
            workspace: $workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
