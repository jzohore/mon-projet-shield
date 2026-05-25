<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260515091433 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audit_logs ADD workspace_id UUID NOT NULL');
        $this->addSql('ALTER TABLE audit_logs ADD CONSTRAINT FK_D62F285882D40A1F FOREIGN KEY (workspace_id) REFERENCES workspaces (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_D62F285882D40A1F ON audit_logs (workspace_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audit_logs DROP CONSTRAINT FK_D62F285882D40A1F');
        $this->addSql('DROP INDEX IDX_D62F285882D40A1F');
        $this->addSql('ALTER TABLE audit_logs DROP workspace_id');
    }
}
