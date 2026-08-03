<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260730124447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workspaces ADD email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE workspaces DROP max_ai_meeting_minutes');
        $this->addSql('ALTER TABLE workspaces DROP consumed_ai_meeting_minutes');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workspaces ADD max_ai_meeting_minutes INT DEFAULT 120 NOT NULL');
        $this->addSql('ALTER TABLE workspaces ADD consumed_ai_meeting_minutes INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE workspaces DROP email');
    }
}
