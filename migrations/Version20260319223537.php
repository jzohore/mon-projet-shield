<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260319223537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE kyc_documents (id UUID NOT NULL, slug_id VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, storage_path VARCHAR(255) DEFAULT NULL, rejection_reason TEXT DEFAULT NULL, expires_at DATE DEFAULT NULL, folder_id UUID NOT NULL, stakeholder_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9A3DD3C0311966CE ON kyc_documents (slug_id)');
        $this->addSql('CREATE INDEX IDX_9A3DD3C0162CB942 ON kyc_documents (folder_id)');
        $this->addSql('CREATE INDEX IDX_9A3DD3C0F2D3711A ON kyc_documents (stakeholder_id)');
        $this->addSql('CREATE TABLE kyc_folders (id UUID NOT NULL, slug_id VARCHAR(255) NOT NULL, contact_first_name VARCHAR(100) NOT NULL, contact_last_name VARCHAR(100) NOT NULL, contact_email VARCHAR(255) NOT NULL, company_name VARCHAR(255) DEFAULT NULL, siret VARCHAR(14) DEFAULT NULL, status VARCHAR(255) NOT NULL, share_token VARCHAR(64) NOT NULL, share_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, workspace_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_989D5EB8311966CE ON kyc_folders (slug_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_989D5EB8D6594DD6 ON kyc_folders (share_token)');
        $this->addSql('CREATE INDEX IDX_989D5EB882D40A1F ON kyc_folders (workspace_id)');
        $this->addSql('CREATE TABLE stake_holders (id UUID NOT NULL, slug_id VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, role VARCHAR(255) NOT NULL, ownership_percentage DOUBLE PRECISION DEFAULT NULL, folder_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3ECD0891311966CE ON stake_holders (slug_id)');
        $this->addSql('CREATE INDEX IDX_3ECD0891162CB942 ON stake_holders (folder_id)');
        $this->addSql('ALTER TABLE kyc_documents ADD CONSTRAINT FK_9A3DD3C0162CB942 FOREIGN KEY (folder_id) REFERENCES kyc_folders (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE kyc_documents ADD CONSTRAINT FK_9A3DD3C0F2D3711A FOREIGN KEY (stakeholder_id) REFERENCES stake_holders (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE kyc_folders ADD CONSTRAINT FK_989D5EB882D40A1F FOREIGN KEY (workspace_id) REFERENCES workspaces (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE stake_holders ADD CONSTRAINT FK_3ECD0891162CB942 FOREIGN KEY (folder_id) REFERENCES kyc_folders (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE kyc_documents DROP CONSTRAINT FK_9A3DD3C0162CB942');
        $this->addSql('ALTER TABLE kyc_documents DROP CONSTRAINT FK_9A3DD3C0F2D3711A');
        $this->addSql('ALTER TABLE kyc_folders DROP CONSTRAINT FK_989D5EB882D40A1F');
        $this->addSql('ALTER TABLE stake_holders DROP CONSTRAINT FK_3ECD0891162CB942');
        $this->addSql('DROP TABLE kyc_documents');
        $this->addSql('DROP TABLE kyc_folders');
        $this->addSql('DROP TABLE stake_holders');
    }
}
