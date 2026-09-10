<?php

declare(strict_types=1);

namespace App\Application\User\DTO\Response;

use App\Domain\User\Entity\Admin;
use App\Domain\User\Enum\AdminRole;

final readonly class AdminAccountView
{
    public function __construct(
        public string $slugId,
        public string $email,
        public string $firstName,
        public string $lastName,
        public ?string $phoneNumber,
        /** Valeur d'un AdminRole. */
        public string $role,
        public string $roleLabel,
        public bool $isActive,
        public bool $isSuspended,
        public bool $isArchived,
    ) {
    }

    public static function fromEntity(Admin $admin): self
    {
        $role = AdminRole::OPERATOR;
        foreach ($admin->getRoles() as $value) {
            $candidate = AdminRole::tryFrom($value);
            if (null !== $candidate) {
                $role = $candidate;
                break;
            }
        }

        return new self(
            slugId: $admin->slugId,
            email: $admin->email,
            firstName: $admin->firstName,
            lastName: $admin->lastName,
            phoneNumber: $admin->phoneNumber,
            role: $role->value,
            roleLabel: $role->getLabel(),
            isActive: $admin->isActif,
            isSuspended: $admin->isSuspended,
            isArchived: $admin->isArchived,
        );
    }
}
