<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260312151926 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE workspaces_invitations (id UUID NOT NULL, email VARCHAR(180) NOT NULL, slug_id VARCHAR(255) NOT NULL, invitation_status VARCHAR(50) DEFAULT NULL, invited_role VARCHAR(50) DEFAULT NULL, magic_link_token VARCHAR(255) DEFAULT NULL, magic_link_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, workspace_id UUID DEFAULT NULL, owner_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_47A29EA2E7927C74 ON workspaces_invitations (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_47A29EA2311966CE ON workspaces_invitations (slug_id)');
        $this->addSql('CREATE INDEX IDX_47A29EA282D40A1F ON workspaces_invitations (workspace_id)');
        $this->addSql('CREATE INDEX IDX_47A29EA27E3C61F9 ON workspaces_invitations (owner_id)');
        $this->addSql('ALTER TABLE workspaces_invitations ADD CONSTRAINT FK_47A29EA282D40A1F FOREIGN KEY (workspace_id) REFERENCES workspaces (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE workspaces_invitations ADD CONSTRAINT FK_47A29EA27E3C61F9 FOREIGN KEY (owner_id) REFERENCES "users" (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workspaces_invitations DROP CONSTRAINT FK_47A29EA282D40A1F');
        $this->addSql('ALTER TABLE workspaces_invitations DROP CONSTRAINT FK_47A29EA27E3C61F9');
        $this->addSql('DROP TABLE workspaces_invitations');
    }
}
