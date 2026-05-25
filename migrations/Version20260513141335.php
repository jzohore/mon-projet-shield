<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260513141335 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE compliance_documents (id UUID NOT NULL, slug_id VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, storage_path VARCHAR(255) DEFAULT NULL, rejection_reason TEXT DEFAULT NULL, expires_at DATE DEFAULT NULL, ocr_data JSON DEFAULT NULL, custom_label VARCHAR(255) DEFAULT NULL, is_mandatory BOOLEAN NOT NULL, uploaded_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, folder_id UUID NOT NULL, stakeholder_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EABE6873311966CE ON compliance_documents (slug_id)');
        $this->addSql('CREATE INDEX IDX_EABE6873162CB942 ON compliance_documents (folder_id)');
        $this->addSql('CREATE INDEX IDX_EABE6873F2D3711A ON compliance_documents (stakeholder_id)');
        $this->addSql('CREATE TABLE compliance_folders (id UUID NOT NULL, reference VARCHAR(20) NOT NULL, status VARCHAR(255) NOT NULL, history JSON NOT NULL, share_token VARCHAR(255) DEFAULT NULL, risk_level VARCHAR(255) DEFAULT NULL, diligence_level VARCHAR(255) NOT NULL, next_review_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, submitted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_certified BOOLEAN NOT NULL, metadata JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, workspace_id UUID NOT NULL, assigned_reviewer_id UUID DEFAULT NULL, dtype VARCHAR(255) NOT NULL, first_name VARCHAR(100) DEFAULT NULL, last_name VARCHAR(100) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, company_name VARCHAR(255) DEFAULT NULL, siret VARCHAR(14) DEFAULT NULL, legal_category VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B4046EBEAEA34913 ON compliance_folders (reference)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B4046EBED6594DD6 ON compliance_folders (share_token)');
        $this->addSql('CREATE INDEX IDX_B4046EBE82D40A1F ON compliance_folders (workspace_id)');
        $this->addSql('CREATE INDEX IDX_B4046EBE576DA6DF ON compliance_folders (assigned_reviewer_id)');
        $this->addSql('ALTER TABLE compliance_documents ADD CONSTRAINT FK_EABE6873162CB942 FOREIGN KEY (folder_id) REFERENCES kyc_folders (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE compliance_documents ADD CONSTRAINT FK_EABE6873F2D3711A FOREIGN KEY (stakeholder_id) REFERENCES stake_holders (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE compliance_folders ADD CONSTRAINT FK_B4046EBE82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspaces (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE compliance_folders ADD CONSTRAINT FK_B4046EBE576DA6DF FOREIGN KEY (assigned_reviewer_id) REFERENCES "users" (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE compliance_documents DROP CONSTRAINT FK_EABE6873162CB942');
        $this->addSql('ALTER TABLE compliance_documents DROP CONSTRAINT FK_EABE6873F2D3711A');
        $this->addSql('ALTER TABLE compliance_folders DROP CONSTRAINT FK_B4046EBE82D40A1F');
        $this->addSql('ALTER TABLE compliance_folders DROP CONSTRAINT FK_B4046EBE576DA6DF');
        $this->addSql('DROP TABLE compliance_documents');
        $this->addSql('DROP TABLE compliance_folders');
    }
}
