<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Listener;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\User\Event\AdminAccountActionOccurred;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Trace inaltérable de toutes les mutations d'un compte de l'équipe KYSURE
 * (création, profil, rôle, suspension, réactivation, archivage, suppression).
 * Ces événements ne sont rattachés à aucun cabinet : ils vivent uniquement dans
 * le journal d'audit inter-cabinets du back-office.
 */
#[AsEventListener(event: AdminAccountActionOccurred::class)]
final readonly class AuditLogAdminAccountListener
{
    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    public function __invoke(AdminAccountActionOccurred $event): void
    {
        $payload = [
            'actor_name' => $event->initiatorName,
            'actor_email' => $event->initiatorEmail,
            'target_name' => $event->adminFullName,
            'target_email' => $event->adminEmail,
        ];

        foreach ($event->context as $key => $value) {
            $payload[$key] = $value;
        }

        $this->auditLogRepository->save(AuditLog::initiate(
            eventName: $event->action->auditType(),
            payload: $payload,
        ));
    }
}
