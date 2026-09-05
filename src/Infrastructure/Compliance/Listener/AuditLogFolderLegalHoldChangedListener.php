<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Event\FolderLegalHoldChangedEvent;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class AuditLogFolderLegalHoldChangedListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
        private ComplianceFolderRepositoryInterface $folderRepository,
    ) {
    }

    public function __invoke(FolderLegalHoldChangedEvent $event): void
    {
        $folder = $this->folderRepository->findOneBySlugId($event->folderSlugId);
        Assert::isInstanceOf($folder, ComplianceFolder::class, 'Dossier introuvable pour le verrou de litige.');

        $audit = AuditLog::initiate(
            eventName: $event->placed ? AuditEventType::KYC_LEGAL_HOLD_PLACED : AuditEventType::KYC_LEGAL_HOLD_LIFTED,
            payload: [
                'folder_slug_id' => $folder->slugId,
                'folder_reference' => $folder->reference ?? 'N/A',
                'workspace_name' => $folder->workspace->name,
                'actor_type' => 'workspace_admin',
                'actor_name' => $event->actorName,
                'reason' => $event->reason,
                'changed_at' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
            ],
            workspace: $folder->workspace,
        );

        $this->auditLogRepository->save($audit);
    }
}
