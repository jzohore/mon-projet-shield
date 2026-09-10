<?php

declare(strict_types=1);

namespace App\Application\Support\UseCase;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;

use function Symfony\Component\Clock\now;

use Webmozart\Assert\Assert;

/**
 * Connexion support : un opérateur KYSURE (firewall « admin ») demande à agir en
 * tant qu'un collaborateur d'un cabinet (firewall « main »). On impose un motif,
 * on vérifie l'appartenance (table de jonction workspace_members) et l'état du
 * compte, puis on trace l'entrée dans le journal d'audit du cabinet — visible
 * côté cabinet. La bascule de session est réalisée par le contrôleur (couche
 * Infrastructure) ; ce use case rend le collaborateur cible prêt à être authentifié.
 */
final readonly class StartSupportImpersonationUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    /**
     * @throws \InvalidArgumentException si le motif est trop court, le cabinet
     *                                   suspendu, la cible hors du cabinet ou son compte désactivé
     */
    public function __invoke(
        string $workspaceSlugId,
        string $operatorEmail,
        string $operatorName,
        string $targetEmail,
        string $reason,
    ): User {
        $reason = trim($reason);
        Assert::minLength($reason, 10, 'Un motif d\'accès d\'au moins 10 caractères est obligatoire.');

        $workspace = $this->workspaceRepository->findOneBySlug($workspaceSlugId);
        Assert::isInstanceOf($workspace, Workspace::class, 'Cabinet introuvable.');
        Assert::true($workspace->isActive, 'Connexion support impossible : ce cabinet est suspendu.');

        $normalizedEmail = mb_strtolower(trim($targetEmail));
        $membership = $workspace->members->findFirst(
            static fn (int $_index, WorkspaceMember $member): bool => mb_strtolower(trim($member->user->email)) === $normalizedEmail,
        );
        Assert::isInstanceOf($membership, WorkspaceMember::class, 'Aucun collaborateur ne correspond à cette adresse dans ce cabinet.');

        $target = $membership->user;
        Assert::true($target->isActif, 'Ce compte collaborateur est désactivé.');

        $this->auditLogRepository->save(AuditLog::initiate(
            eventName: AuditEventType::ADMIN_IMPERSONATION_START,
            payload: [
                'operator_email' => $operatorEmail,
                'operator_name' => $operatorName,
                'impersonated_email' => $target->email,
                'impersonated_name' => $target->getFullName(),
                'reason' => $reason,
                'at' => now()->format(\DateTimeInterface::ATOM),
            ],
            workspace: $workspace,
        ));

        return $target;
    }
}
