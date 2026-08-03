<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260710094006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE "clients" (id UUID NOT NULL, email VARCHAR(180) NOT NULL, slug_id VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, phone_number VARCHAR(20) DEFAULT NULL, roles JSON NOT NULL, magic_link_token VARCHAR(255) DEFAULT NULL, magic_link_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, google_authenticator_secret VARCHAR(150) DEFAULT NULL, is_totp_verified BOOLEAN DEFAULT false NOT NULL, is_actif BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C82E74E7927C74 ON "clients" (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C82E74311966CE ON "clients" (slug_id)');
        $this->addSql('CREATE TABLE client_workspaces (client_id UUID NOT NULL, workspace_id UUID NOT NULL, PRIMARY KEY (client_id, workspace_id))');
        $this->addSql('CREATE INDEX IDX_EFBCCE8A19EB6921 ON client_workspaces (client_id)');
        $this->addSql('CREATE INDEX IDX_EFBCCE8A82D40A1F ON client_workspaces (workspace_id)');
        $this->addSql('ALTER TABLE client_workspaces ADD CONSTRAINT FK_EFBCCE8A19EB6921 FOREIGN KEY (client_id) REFERENCES "clients" (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE client_workspaces ADD CONSTRAINT FK_EFBCCE8A82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspaces (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE compliance_folders ADD client_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE compliance_folders ADD CONSTRAINT FK_B4046EBE19EB6921 FOREIGN KEY (client_id) REFERENCES "clients" (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_B4046EBE19EB6921 ON compliance_folders (client_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_workspaces DROP CONSTRAINT FK_EFBCCE8A19EB6921');
        $this->addSql('ALTER TABLE client_workspaces DROP CONSTRAINT FK_EFBCCE8A82D40A1F');
        $this->addSql('DROP TABLE "clients"');
        $this->addSql('DROP TABLE client_workspaces');
        $this->addSql('ALTER TABLE compliance_folders DROP CONSTRAINT FK_B4046EBE19EB6921');
        $this->addSql('DROP INDEX IDX_B4046EBE19EB6921');
        $this->addSql('ALTER TABLE compliance_folders DROP client_id');
    }
}
