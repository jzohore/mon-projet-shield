<?php

namespace App\Domain\Workspace\Enum;

enum InvitedRole: string
{
    case ROLE_WORKSPACE_ADMIN = 'ROLE_WORKSPACE_ADMIN';           // Vient de cliquer sur le lien magique
    case ROLE_WORKSPACE_COLLAB = 'ROLE_WORKSPACE_COLLAB';       // A terminé, accès total

    public function getLabel(): string
    {
        return match ($this) {
            self::ROLE_WORKSPACE_ADMIN => 'Administrateur',
            self::ROLE_WORKSPACE_COLLAB => 'Collaborateur',
        };
    }
    public static function getGroupedChoices(): array
    {
        return [
            self::ROLE_WORKSPACE_ADMIN->getLabel() => self::ROLE_WORKSPACE_ADMIN,
            self::ROLE_WORKSPACE_COLLAB->getLabel() => self::ROLE_WORKSPACE_COLLAB,
        ];
    }

    public function getColorClasses(): string
    {
        return match ($this) {
            self::ROLE_WORKSPACE_ADMIN => 'bg-indigo-50 text-indigo-700 border-indigo-100',
            self::ROLE_WORKSPACE_COLLAB => 'bg-blue-50 text-blue-700 border-blue-100',
        };
    }
}
