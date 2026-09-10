<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Team;

use App\Application\User\DTO\Request\CreateAdminAccountInput;
use App\Domain\User\Entity\Admin;
use App\Domain\User\Enum\AdminAccountAction;
use App\Domain\User\Enum\AdminRole;
use App\Domain\User\Event\AdminAccountActionOccurred;
use App\Domain\User\Repository\AdminRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

/**
 * Crée un compte de l'équipe KYSURE. Le compte est actif immédiatement ; un lien
 * de première connexion (valable 7 jours) est généré et transmis par e-mail via
 * l'événement AdminAccountActionOccurred. Réservé à un ROLE_SUPER_ADMIN (garde
 * posée dans le contrôleur).
 */
final readonly class CreateAdminAccountUseCase
{
    public function __construct(
        private AdminRepositoryInterface $adminRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(CreateAdminAccountInput $input, string $initiatorEmail, string $initiatorName): Admin
    {
        $email = mb_strtolower(trim($input->email));
        Assert::email($email, 'Adresse e-mail invalide.');
        Assert::null(
            $this->adminRepository->findByEmail($email),
            'Un compte de l\'équipe existe déjà avec cette adresse.',
        );

        $role = AdminRole::tryFrom($input->role);
        Assert::notNull($role, 'Rôle invalide.');

        $firstName = trim($input->firstName);
        $lastName = trim($input->lastName);
        $phone = null !== $input->phoneNumber && '' !== trim($input->phoneNumber) ? trim($input->phoneNumber) : null;

        $admin = Admin::initiate(
            email: $email,
            firstName: $firstName,
            lastName: $lastName,
            isActif: true,
            roles: [$role->value],
        );
        $admin->updateProfile($firstName, $lastName, $phone);

        $this->adminRepository->save($admin);

        $this->eventDispatcher->dispatch(new AdminAccountActionOccurred(
            action: AdminAccountAction::CREATED,
            adminSlugId: $admin->slugId,
            adminEmail: $admin->email,
            adminFirstName: $admin->firstName,
            adminFullName: $admin->getFullName(),
            initiatorEmail: $initiatorEmail,
            initiatorName: $initiatorName,
            context: ['role' => $role->getLabel()],
        ));

        return $admin;
    }
}
