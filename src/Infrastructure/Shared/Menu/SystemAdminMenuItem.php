<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Menu;

enum SystemAdminMenuItem: string
{
    case DASHBOARD = 'dashboard';
    case WORKSPACES = 'workspaces';
    case SUPPORT = 'support';
    case SUBSCRIPTIONS = 'subscriptions';
    case COMPLIANCE = 'compliance';
    case ADMINS = 'admins';
    case AUDIT_LOGS = 'audit_logs';
    case ANALYTICS = 'analytics';

    public function getLabel(): string
    {
        return match ($this) {
            self::DASHBOARD => 'Tableau de Bord',
            self::WORKSPACES => 'Workspaces & Clients',
            self::SUPPORT => 'Support & Tickets', // 👈 Nouveau label
            self::SUBSCRIPTIONS => 'Abonnements & MRR',
            self::COMPLIANCE => 'Règles Compliance',
            self::ADMINS => 'Équipe Super-Admin',
            self::AUDIT_LOGS => 'Journal d\'Audit',
            self::ANALYTICS => 'Analytique',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::DASHBOARD => 'lucide:layout-dashboard',
            self::WORKSPACES => 'lucide:building-2',
            self::SUPPORT => 'lucide:life-buoy', // 👈 Icône cohérente avec le côté client
            self::SUBSCRIPTIONS => 'lucide:credit-card',
            self::COMPLIANCE => 'lucide:scale',
            self::ADMINS => 'lucide:shield-check',
            self::AUDIT_LOGS => 'lucide:scroll-text',
            self::ANALYTICS => 'famicons:analytics',
        };
    }

    public function getRoute(): string
    {
        return match ($this) {
            self::DASHBOARD => 'admin_dashboard',
            self::WORKSPACES => 'admin_organizations_list',
            self::SUPPORT => 'admin_support_list', // 👈 Nouvelle route à créer
            self::SUBSCRIPTIONS => 'admin_compliance_list',
            self::COMPLIANCE => 'admin_compliance_list',
            self::ADMINS => 'account_admin_list',
            self::AUDIT_LOGS => 'admin_audit_logs_list',
            self::ANALYTICS => 'admin_analytics_show',
        };
    }
}
