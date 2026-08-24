<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260820131325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE compliance_meeting_recording (id UUID NOT NULL, slug_id VARCHAR(255) NOT NULL, session_id VARCHAR(255) NOT NULL, s3_path VARCHAR(500) NOT NULL, duration_in_seconds INT NOT NULL, gemini_raw_output JSON DEFAULT NULL, recorded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, compliance_folder_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7E5F2541311966CE ON compliance_meeting_recording (slug_id)');
        $this->addSql('CREATE INDEX IDX_7E5F25415E22A3B9 ON compliance_meeting_recording (compliance_folder_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_session_folder ON compliance_meeting_recording (session_id, compliance_folder_id)');
        $this->addSql('ALTER TABLE compliance_meeting_recording ADD CONSTRAINT FK_7E5F25415E22A3B9 FOREIGN KEY (compliance_folder_id) REFERENCES compliance_folders (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE compliance_meeting_recording DROP CONSTRAINT FK_7E5F25415E22A3B9');
        $this->addSql('DROP TABLE compliance_meeting_recording');
    }
}
