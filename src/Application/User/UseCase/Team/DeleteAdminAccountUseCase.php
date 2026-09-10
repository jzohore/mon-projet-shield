<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Team;

use App\Domain\User\Entity\Admin;
use App\Domain\User\Enum\AdminAccountAction;
use App\Domain\User\Enum\AdminRole;
use App\Domain\User\Event\AdminAccountActionOccurred;
use App\Domain\User\Repository\AdminRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

/**
 * Suppression définitive d'un compte de l'équipe KYSURE. Irréversible : l'audit
 * ne portera plus que des scalaires. Un opérateur ne peut pas se supprimer
 * lui-même et on ne supprime jamais le dernier administrateur actif.
 */
final readonly class DeleteAdminAccountUseCase
{
    public function __construct(
        private AdminRepositoryInterface $adminRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(string $slugId, string $initiatorEmail, string $initiatorName): void
    {
        $admin = $this->adminRepository->findBySlugId($slugId);
        Assert::isInstanceOf($admin, Admin::class, 'Compte de l\'équipe introuvable.');

        if (mb_strtolower($admin->email) === mb_strtolower(trim($initiatorEmail))) {
            throw new \DomainException('Vous ne pouvez pas supprimer votre propre compte.');
        }

        $isActiveSuperAdmin = $admin->isActif
            && in_array(AdminRole::SUPER_ADMIN->value, $admin->getRoles(), true);
        if ($isActiveSuperAdmin && $this->adminRepository->countActiveSuperAdmins() <= 1) {
            throw new \DomainException('Impossible : c\'est le dernier administrateur actif de la plateforme.');
        }

        $snapshot = new AdminAccountActionOccurred(
            action: AdminAccountAction::DELETED,
            adminSlugId: $admin->slugId,
            adminEmail: $admin->email,
            adminFirstName: $admin->firstName,
            adminFullName: $admin->getFullName(),
            initiatorEmail: $initiatorEmail,
            initiatorName: $initiatorName,
        );

        $this->adminRepository->delete($admin);

        $this->eventDispatcher->dispatch($snapshot);
    }
}
