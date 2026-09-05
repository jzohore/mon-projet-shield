<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260905191830 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'KYC : points de vigilance OCR (analyse non décisionnaire) + rattachement d\'un screening à son dossier de conformité';
    }

    public function up(Schema $schema): void
    {
        // Additif : colonnes nullables, FK ON DELETE SET NULL — pas de verrou long.
        $this->addSql('ALTER TABLE compliance_documents ADD ocr_findings JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE compliance_documents ADD ocr_validator_version VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE screening_audits ADD compliance_folder_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE screening_audits ADD CONSTRAINT FK_D6BF21185E22A3B9 FOREIGN KEY (compliance_folder_id) REFERENCES compliance_folders (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_D6BF21185E22A3B9 ON screening_audits (compliance_folder_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE compliance_documents DROP ocr_findings');
        $this->addSql('ALTER TABLE compliance_documents DROP ocr_validator_version');
        $this->addSql('ALTER TABLE screening_audits DROP CONSTRAINT FK_D6BF21185E22A3B9');
        $this->addSql('DROP INDEX IDX_D6BF21185E22A3B9');
        $this->addSql('ALTER TABLE screening_audits DROP compliance_folder_id');
    }
}
