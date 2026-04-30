<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260425203631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_4778a0182d40a1f');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4778A0182D40A1F ON subscriptions (workspace_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_4778A0182D40A1F');
        $this->addSql('CREATE INDEX idx_4778a0182d40a1f ON subscriptions (workspace_id)');
    }
}
