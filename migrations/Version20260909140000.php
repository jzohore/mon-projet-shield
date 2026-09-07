<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Retrait du sous-système « crédits » : tables products / wallet_transactions,
 * colonnes workspaces.balance / workspaces.transactions. Modèle unique désormais :
 * abonnement au siège + minutes d'entretien métrées.
 */
final class Version20260909140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Billing : suppression du système de crédits (products, wallet_transactions, workspaces.balance)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wallet_transactions DROP CONSTRAINT IF EXISTS fk_a50205e282d40a1f');
        $this->addSql('DROP TABLE IF EXISTS wallet_transactions');
        $this->addSql('DROP TABLE IF EXISTS products');
        $this->addSql('ALTER TABLE workspaces DROP COLUMN IF EXISTS balance');
        $this->addSql('ALTER TABLE workspaces DROP COLUMN IF EXISTS transactions');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workspaces ADD balance INT DEFAULT 2');
        $this->addSql('ALTER TABLE workspaces ADD transactions JSON DEFAULT NULL');
        // Les tables products / wallet_transactions ne sont pas recréées (schéma d'origine perdu).
    }
}
