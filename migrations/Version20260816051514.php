<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260816051514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workspaces ADD meeting_minutes_allocated INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE workspaces ADD meeting_seconds_consumed INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE workspaces ALTER email TYPE VARCHAR(180)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7FE8F3CBE7927C74 ON workspaces (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_7FE8F3CBE7927C74');
        $this->addSql('ALTER TABLE workspaces DROP meeting_minutes_allocated');
        $this->addSql('ALTER TABLE workspaces DROP meeting_seconds_consumed');
        $this->addSql('ALTER TABLE workspaces ALTER email TYPE VARCHAR(255)');
    }
}
