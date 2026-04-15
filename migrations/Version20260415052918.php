<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260415052918 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE screening_audits ADD owner_id UUID NOT NULL');
        $this->addSql('ALTER TABLE screening_audits ADD CONSTRAINT FK_D6BF21187E3C61F9 FOREIGN KEY (owner_id) REFERENCES "users" (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_D6BF21187E3C61F9 ON screening_audits (owner_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE screening_audits DROP CONSTRAINT FK_D6BF21187E3C61F9');
        $this->addSql('DROP INDEX IDX_D6BF21187E3C61F9');
        $this->addSql('ALTER TABLE screening_audits DROP owner_id');
    }
}
