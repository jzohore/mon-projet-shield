<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260409122600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE screening_audits (id UUID NOT NULL, slug_id VARCHAR(255) NOT NULL, query VARCHAR(255) NOT NULL, results JSON NOT NULL, total_matches INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status VARCHAR(255) NOT NULL, pdf_path VARCHAR(255) DEFAULT NULL, workspace_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D6BF2118311966CE ON screening_audits (slug_id)');
        $this->addSql('CREATE INDEX IDX_D6BF211882D40A1F ON screening_audits (workspace_id)');
        $this->addSql('ALTER TABLE screening_audits ADD CONSTRAINT FK_D6BF211882D40A1F FOREIGN KEY (workspace_id) REFERENCES workspaces (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE screening_audits DROP CONSTRAINT FK_D6BF211882D40A1F');
        $this->addSql('DROP TABLE screening_audits');
    }
}
