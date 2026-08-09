<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260717092412 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE compliance_folders ADD post_meeting_report JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE compliance_folders ADD current_recording_started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE compliance_folders ADD total_audio_duration_seconds INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE compliance_folders DROP live_insights');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE compliance_folders ADD live_insights JSONB DEFAULT NULL');
        $this->addSql('ALTER TABLE compliance_folders DROP post_meeting_report');
        $this->addSql('ALTER TABLE compliance_folders DROP current_recording_started_at');
        $this->addSql('ALTER TABLE compliance_folders DROP total_audio_duration_seconds');
    }
}
