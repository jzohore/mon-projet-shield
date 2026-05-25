<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260515100312 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audit_logs DROP CONSTRAINT fk_d62f285810daf24a');
        $this->addSql('DROP INDEX idx_d62f285810daf24a');
        $this->addSql('ALTER TABLE audit_logs ADD actor VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE audit_logs DROP actor_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D62F2858447556F9 ON audit_logs (actor)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_D62F2858447556F9');
        $this->addSql('ALTER TABLE audit_logs ADD actor_id UUID NOT NULL');
        $this->addSql('ALTER TABLE audit_logs DROP actor');
        $this->addSql('ALTER TABLE audit_logs ADD CONSTRAINT fk_d62f285810daf24a FOREIGN KEY (actor_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_d62f285810daf24a ON audit_logs (actor_id)');
    }
}
