<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * État de la relation d'affaires par cabinet : nouvelle table
 * client_workspace_relations. Le compte Client reste mutualisé entre cabinets ;
 * l'axe actif/clôturé devient propre à chaque cabinet.
 *
 * Reprise : une relation active par lien client_workspaces existant, clôturée
 * (ended_at = now) quand le compte était globalement inactif.
 */
final class Version20260906120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Relation d\'affaires client <-> cabinet (état par cabinet)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE client_workspace_relations (
                id UUID NOT NULL,
                client_id UUID NOT NULL,
                workspace_id UUID NOT NULL,
                started_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                ended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                end_reason VARCHAR(40) DEFAULT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        $this->addSql('CREATE UNIQUE INDEX uniq_client_workspace_rel ON client_workspace_relations (client_id, workspace_id)');
        $this->addSql('CREATE INDEX idx_cwr_client ON client_workspace_relations (client_id)');
        $this->addSql('CREATE INDEX idx_cwr_workspace ON client_workspace_relations (workspace_id)');
        $this->addSql('ALTER TABLE client_workspace_relations ADD CONSTRAINT fk_cwr_client FOREIGN KEY (client_id) REFERENCES "clients" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE client_workspace_relations ADD CONSTRAINT fk_cwr_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Reprise des rattachements existants.
        $this->addSql(<<<'SQL'
            INSERT INTO client_workspace_relations (id, client_id, workspace_id, started_at, ended_at, end_reason)
            SELECT gen_random_uuid(),
                   cw.client_id,
                   cw.workspace_id,
                   c.created_at,
                   CASE WHEN c.is_actif = false THEN NOW() ELSE NULL END,
                   NULL
            FROM client_workspaces cw
            JOIN "clients" c ON c.id = cw.client_id
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE client_workspace_relations');
    }
}
