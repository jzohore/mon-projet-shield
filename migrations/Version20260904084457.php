<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Accusé de réception du DER en interne (remplace la signature DocuSeal).
 *
 * Additive : nouvelle table `compliance_der_acknowledgement` + jeton d'accès
 * nominatif sur `compliance_documents`. Aucune colonne existante modifiée ou
 * supprimée. L'index `uniq_der_ack_document_in_force` (partiel `WHERE
 * revoked_at IS NULL`) garantit un accusé en vigueur au plus par DER.
 */
final class Version20260904084457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Accusé de réception du DER : table compliance_der_acknowledgement + jeton d\'accès sur compliance_documents';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE compliance_der_acknowledgement (id UUID NOT NULL, slug_id VARCHAR(255) NOT NULL, acknowledged_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, pdf_sha256 VARCHAR(64) NOT NULL, pdf_storage_path VARCHAR(255) NOT NULL, declared_name VARCHAR(255) NOT NULL, recipient_email VARCHAR(180) NOT NULL, statement_text TEXT NOT NULL, statement_version VARCHAR(20) NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, revoked_by_name VARCHAR(255) DEFAULT NULL, revoke_reason TEXT DEFAULT NULL, compliance_document_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D8932413311966CE ON compliance_der_acknowledgement (slug_id)');
        $this->addSql('CREATE INDEX IDX_D8932413C915F135 ON compliance_der_acknowledgement (compliance_document_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_der_ack_document_in_force ON compliance_der_acknowledgement (compliance_document_id) WHERE (revoked_at IS NULL)');
        $this->addSql('ALTER TABLE compliance_der_acknowledgement ADD CONSTRAINT FK_D8932413C915F135 FOREIGN KEY (compliance_document_id) REFERENCES compliance_documents (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE compliance_documents ADD acknowledgement_token_hash VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE compliance_documents ADD acknowledgement_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EABE6873D16B5E47 ON compliance_documents (acknowledgement_token_hash)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE compliance_der_acknowledgement DROP CONSTRAINT FK_D8932413C915F135');
        $this->addSql('DROP TABLE compliance_der_acknowledgement');
        $this->addSql('DROP INDEX UNIQ_EABE6873D16B5E47');
        $this->addSql('ALTER TABLE compliance_documents DROP acknowledgement_token_hash');
        $this->addSql('ALTER TABLE compliance_documents DROP acknowledgement_token_expires_at');
    }
}
