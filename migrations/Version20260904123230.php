<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Attestation PDF de l'accusé de réception du DER. Additive.
 */
final class Version20260904123230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'DER : attestation accusé de réception (certificate_storage_path, certificate_sha256)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE compliance_der_acknowledgement ADD certificate_storage_path VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE compliance_der_acknowledgement ADD certificate_sha256 VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE compliance_der_acknowledgement DROP certificate_storage_path');
        $this->addSql('ALTER TABLE compliance_der_acknowledgement DROP certificate_sha256');
    }
}
