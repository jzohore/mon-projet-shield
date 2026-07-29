<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Menu;

enum ComplianceMenuItem: string
{
    case DASHBOARD = 'dashboard';
    case DOSSIERS = 'folders';
    case ADVISORY = 'advisory';
    case SCREENING = 'screening';
    case STAKEHOLDERS = 'stakeholders';
    case DOCUMENTS = 'documents';

    public function getLabel(): string
    {
        return match ($this) {
            self::DASHBOARD => 'Tableau de Bord',
            self::DOSSIERS => 'Dossiers',
            self::ADVISORY => 'Rapports Advisory',
            self::SCREENING => 'Criblage LCB-FT',
            self::STAKEHOLDERS => 'Parties Prenantes',
            self::DOCUMENTS => 'Documents Collectés',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::DASHBOARD => 'lucide:layout-dashboard',
            self::DOSSIERS => 'lucide:shield-check',
            self::ADVISORY => 'lucide:sparkles',
            self::SCREENING => 'lucide:user-round-search',
            self::STAKEHOLDERS => 'lucide:users-round',
            self::DOCUMENTS => 'lucide:folder-open',
        };
    }

    public function getRoute(): string
    {
        return match ($this) {
            self::DASHBOARD => 'app_dashboard',
            self::DOSSIERS => 'app_compliance_list',
            self::ADVISORY => 'app_employees_list', // À ajuster vers app_advisory_list plus tard
            self::SCREENING => 'app_screening_list',
            self::STAKEHOLDERS => 'app_employees_list', // À ajuster vers app_stakeholders_list
            self::DOCUMENTS => 'app_employees_list',
        };
    }

    /**
     * Détermine si l'item doit afficher un compteur dynamique.
     */
    public function hasBadge(): bool
    {
        return self::DOSSIERS === $this;
    }
}
