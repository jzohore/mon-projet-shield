<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

/**
 * Rôles attribuables aux membres de l'équipe KYSURE (back-office).
 *
 * La hiérarchie Symfony (security.yaml) fait que SUPER_ADMIN hérite de OPERATOR :
 * les deux ouvrent l'accès à /admin, seul SUPER_ADMIN peut gérer l'équipe et les
 * zones sensibles (journal d'audit inter-cabinets, abonnements, dossiers
 * compliance, connexion support).
 */
enum AdminRole: string
{
    case SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    case OPERATOR = 'ROLE_ADMIN';

    public function getLabel(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Administrateur',
            self::OPERATOR => 'Opérateur',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Accès complet : équipe KYSURE, journal d\'audit, abonnements, dossiers compliance, connexion support.',
            self::OPERATOR => 'Accès back-office standard : tableau de bord, cabinets & clients, support. Ne peut pas gérer l\'équipe ni les zones sensibles.',
        };
    }

    public function getBadgeClasses(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            self::OPERATOR => 'bg-slate-100 text-slate-600 border-slate-200',
        };
    }

    /**
     * @return array<string, self> libellé => rôle, pour un ChoiceType
     */
    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $role) {
            $choices[$role->getLabel()] = $role;
        }

        return $choices;
    }

    /**
     * Normalise une liste de chaînes en rôles valides et dédupliqués.
     *
     * @param list<string> $roles
     *
     * @return list<string>
     */
    public static function sanitize(array $roles): array
    {
        $valid = [];
        foreach ($roles as $role) {
            if (null !== self::tryFrom($role)) {
                $valid[$role] = $role;
            }
        }

        return array_values($valid);
    }
}
