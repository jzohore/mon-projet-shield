<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Délégation bornée : un administrateur de cabinet ouvre (ou non) certaines
 * actions aux collaborateurs, et choisit la posture de validation des actes.
 */
final class Version20260909190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'workspaces : droits délégués aux collaborateurs + mode de validation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workspaces ADD collab_can_invite BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE workspaces ADD collab_can_manage_portfolio BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE workspaces ADD collab_can_edit_cabinet BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE workspaces ADD collab_can_archive_folder BOOLEAN DEFAULT true NOT NULL');
        $this->addSql("ALTER TABLE workspaces ADD validation_mode VARCHAR(20) DEFAULT 'delegated' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workspaces DROP collab_can_invite');
        $this->addSql('ALTER TABLE workspaces DROP collab_can_manage_portfolio');
        $this->addSql('ALTER TABLE workspaces DROP collab_can_edit_cabinet');
        $this->addSql('ALTER TABLE workspaces DROP collab_can_archive_folder');
        $this->addSql('ALTER TABLE workspaces DROP validation_mode');
    }
}
