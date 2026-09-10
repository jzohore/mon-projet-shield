<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Team;

use App\Domain\User\Entity\Admin;
use App\Domain\User\Enum\AdminRole;
use App\Domain\User\Enum\AdminStatusChange;
use App\Domain\User\Event\AdminAccountActionOccurred;
use App\Domain\User\Repository\AdminRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

/**
 * Suspend, réactive ou archive un compte de l'équipe KYSURE. Idempotent. Un
 * opérateur ne peut pas se cibler lui-même, et on ne suspend / n'archive jamais
 * le dernier administrateur actif.
 */
final readonly class ChangeAdminAccountStatusUseCase
{
    public function __construct(
        private AdminRepositoryInterface $adminRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(
        string $slugId,
        AdminStatusChange $change,
        string $initiatorEmail,
        string $initiatorName,
    ): void {
        $admin = $this->adminRepository->findBySlugId($slugId);
        Assert::isInstanceOf($admin, Admin::class, 'Compte de l\'équipe introuvable.');

        if (mb_strtolower($admin->email) === mb_strtolower(trim($initiatorEmail))) {
            throw new \DomainException('Vous ne pouvez pas modifier le statut de votre propre compte.');
        }

        // Idempotence
        $alreadyInTargetState = match ($change) {
            AdminStatusChange::SUSPEND => $admin->isSuspended && !$admin->isArchived,
            AdminStatusChange::REACTIVATE => $admin->isActif,
            AdminStatusChange::ARCHIVE => $admin->isArchived,
        };
        if ($alreadyInTargetState) {
            return;
        }

        if (AdminStatusChange::SUSPEND === $change || AdminStatusChange::ARCHIVE === $change) {
            $this->assertNotLastActiveSuperAdmin($admin);
        }

        match ($change) {
            AdminStatusChange::SUSPEND => $admin->suspend(),
            AdminStatusChange::REACTIVATE => $admin->reactivate(),
            AdminStatusChange::ARCHIVE => $admin->archive(),
        };

        $this->adminRepository->save($admin);

        $this->eventDispatcher->dispatch(new AdminAccountActionOccurred(
            action: $change->toAccountAction(),
            adminSlugId: $admin->slugId,
            adminEmail: $admin->email,
            adminFirstName: $admin->firstName,
            adminFullName: $admin->getFullName(),
            initiatorEmail: $initiatorEmail,
            initiatorName: $initiatorName,
        ));
    }

    private function assertNotLastActiveSuperAdmin(Admin $admin): void
    {
        $isActiveSuperAdmin = $admin->isActif
            && in_array(AdminRole::SUPER_ADMIN->value, $admin->getRoles(), true);

        if ($isActiveSuperAdmin && $this->adminRepository->countActiveSuperAdmins() <= 1) {
            throw new \DomainException('Impossible : c\'est le dernier administrateur actif de la plateforme.');
        }
    }
}
