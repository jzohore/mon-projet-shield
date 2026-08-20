<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260818220757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE compliance_folders ADD is_accept_recording BOOLEAN DEFAULT false');
        $this->addSql('ALTER TABLE workspaces DROP is_accept_recording');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE compliance_folders DROP is_accept_recording');
        $this->addSql('ALTER TABLE workspaces ADD is_accept_recording BOOLEAN DEFAULT false');
    }
}
