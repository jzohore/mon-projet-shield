<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Relation client <-> cabinet : état « en attente de confirmation ».
 *
 * Tant qu'aucun DER n'est accusé, le cabinet ne voit que le nom qu'il a saisi
 * (invited_first_name / invited_last_name), jamais les coordonnées maîtres du
 * compte — anti-énumération d'e-mails. `confirmed_at` est posé au 1er accusé.
 *
 * Reprise : toutes les relations existantes sont considérées confirmées
 * (confirmed_at = started_at) et leur nom saisi = le nom du compte.
 */
final class Version20260906130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Relation client <-> cabinet : état en attente de confirmation (anti-énumération)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client_workspace_relations ADD invited_first_name VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE client_workspace_relations ADD invited_last_name VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE client_workspace_relations ADD confirmed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        // Reprise : relations existantes = confirmées, nom saisi = nom du compte.
        $this->addSql(<<<'SQL'
            UPDATE client_workspace_relations rel
            SET invited_first_name = c.first_name,
                invited_last_name  = c.last_name,
                confirmed_at       = rel.started_at
            FROM "clients" c
            WHERE c.id = rel.client_id
            SQL);

        $this->addSql('ALTER TABLE client_workspace_relations ALTER COLUMN invited_first_name SET NOT NULL');
        $this->addSql('ALTER TABLE client_workspace_relations ALTER COLUMN invited_last_name SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client_workspace_relations DROP invited_first_name');
        $this->addSql('ALTER TABLE client_workspace_relations DROP invited_last_name');
        $this->addSql('ALTER TABLE client_workspace_relations DROP confirmed_at');
    }
}
