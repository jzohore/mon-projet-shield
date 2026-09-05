<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260905213337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cycle de vie rétention LCB-FT du dossier : fin de relation d\'affaires + échéance de purge + verrou de litige';
    }

    public function up(Schema $schema): void
    {
        // Additif : colonnes nullables (ou bool default false) — pas de verrou long.
        $this->addSql('ALTER TABLE compliance_folders ADD relationship_ended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE compliance_folders ADD relationship_end_reason TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE compliance_folders ADD purge_due_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE compliance_folders ADD is_under_legal_hold BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE compliance_folders ADD legal_hold_reason TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE compliance_folders ADD legal_hold_placed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE compliance_folders DROP relationship_ended_at');
        $this->addSql('ALTER TABLE compliance_folders DROP relationship_end_reason');
        $this->addSql('ALTER TABLE compliance_folders DROP purge_due_at');
        $this->addSql('ALTER TABLE compliance_folders DROP is_under_legal_hold');
        $this->addSql('ALTER TABLE compliance_folders DROP legal_hold_reason');
        $this->addSql('ALTER TABLE compliance_folders DROP legal_hold_placed_at');
    }
}
