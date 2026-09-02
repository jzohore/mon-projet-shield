<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260901143307 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE compliance_validated_meeting_report (id UUID NOT NULL, slug_id VARCHAR(255) NOT NULL, content JSON NOT NULL, source_recording_slugs JSON NOT NULL, content_hash VARCHAR(64) NOT NULL, version INT DEFAULT 1 NOT NULL, validated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, validated_by_name VARCHAR(255) NOT NULL, revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, revoked_by_name VARCHAR(255) DEFAULT NULL, revoke_reason TEXT DEFAULT NULL, compliance_folder_id UUID NOT NULL, validated_by_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2417DD9311966CE ON compliance_validated_meeting_report (slug_id)');
        $this->addSql('CREATE INDEX IDX_2417DD95E22A3B9 ON compliance_validated_meeting_report (compliance_folder_id)');
        $this->addSql('CREATE INDEX IDX_2417DD9C69DE5E5 ON compliance_validated_meeting_report (validated_by_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_folder_report_version ON compliance_validated_meeting_report (compliance_folder_id, version)');
        $this->addSql('ALTER TABLE compliance_validated_meeting_report ADD CONSTRAINT FK_2417DD95E22A3B9 FOREIGN KEY (compliance_folder_id) REFERENCES compliance_folders (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE compliance_validated_meeting_report ADD CONSTRAINT FK_2417DD9C69DE5E5 FOREIGN KEY (validated_by_id) REFERENCES "users" (id) ON DELETE RESTRICT NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE compliance_validated_meeting_report DROP CONSTRAINT FK_2417DD95E22A3B9');
        $this->addSql('ALTER TABLE compliance_validated_meeting_report DROP CONSTRAINT FK_2417DD9C69DE5E5');
        $this->addSql('DROP TABLE compliance_validated_meeting_report');
    }
}
