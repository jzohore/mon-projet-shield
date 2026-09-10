<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Team;

use App\Application\User\DTO\Request\UpdateAdminAccountInput;
use App\Domain\User\Entity\Admin;
use App\Domain\User\Enum\AdminAccountAction;
use App\Domain\User\Enum\AdminRole;
use App\Domain\User\Event\AdminAccountActionOccurred;
use App\Domain\User\Repository\AdminRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

/**
 * Met à jour le profil et/ou le rôle d'un compte de l'équipe KYSURE. Émet un
 * événement par changement réel (profil, rôle). Un changement de rôle régénère
 * l'empreinte de session (coupe les sessions ouvertes). On ne retire jamais
 * SUPER_ADMIN au dernier administrateur actif.
 */
final readonly class UpdateAdminAccountUseCase
{
    public function __construct(
        private AdminRepositoryInterface $adminRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(UpdateAdminAccountInput $input, string $initiatorEmail, string $initiatorName): void
    {
        $admin = $this->adminRepository->findBySlugId($input->slugId);
        Assert::isInstanceOf($admin, Admin::class, 'Compte de l\'équipe introuvable.');
        if ($admin->isArchived) {
            throw new \DomainException('Ce compte est archivé : il ne peut plus être modifié.');
        }

        $newRole = AdminRole::tryFrom($input->role);
        Assert::notNull($newRole, 'Rôle invalide.');

        $rolesBefore = $admin->getRoles();
        $losesSuperAdmin = in_array(AdminRole::SUPER_ADMIN->value, $rolesBefore, true)
            && AdminRole::SUPER_ADMIN !== $newRole;
        if ($losesSuperAdmin && $admin->isActif && $this->adminRepository->countActiveSuperAdmins() <= 1) {
            throw new \DomainException('Impossible de rétrograder le dernier administrateur actif.');
        }

        $firstName = trim($input->firstName);
        $lastName = trim($input->lastName);
        $phone = null !== $input->phoneNumber && '' !== trim($input->phoneNumber) ? trim($input->phoneNumber) : null;

        $profileBefore = [$admin->firstName, $admin->lastName, $admin->phoneNumber];
        $admin->updateProfile($firstName, $lastName, $phone);
        $profileChanged = $profileBefore !== [$admin->firstName, $admin->lastName, $admin->phoneNumber];

        $rolesChanged = $rolesBefore !== [$newRole->value];
        if ($rolesChanged) {
            $admin->changeRoles([$newRole->value]);
        }

        if (!$profileChanged && !$rolesChanged) {
            return;
        }

        $this->adminRepository->save($admin);

        if ($profileChanged) {
            $this->dispatch($admin, AdminAccountAction::PROFILE_UPDATED, $initiatorEmail, $initiatorName, []);
        }
        if ($rolesChanged) {
            $this->dispatch($admin, AdminAccountAction::ROLES_CHANGED, $initiatorEmail, $initiatorName, [
                'role_before' => implode(', ', $rolesBefore),
                'role_after' => $newRole->getLabel(),
            ]);
        }
    }

    /**
     * @param array<string, scalar|null> $context
     */
    private function dispatch(Admin $admin, AdminAccountAction $action, string $initiatorEmail, string $initiatorName, array $context): void
    {
        $this->eventDispatcher->dispatch(new AdminAccountActionOccurred(
            action: $action,
            adminSlugId: $admin->slugId,
            adminEmail: $admin->email,
            adminFirstName: $admin->firstName,
            adminFullName: $admin->getFullName(),
            initiatorEmail: $initiatorEmail,
            initiatorName: $initiatorName,
            context: $context,
        ));
    }
}
