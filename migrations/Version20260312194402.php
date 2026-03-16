<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260312194402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_1483a5e976f5c865');
        $this->addSql('ALTER TABLE users ADD is_owner BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE users DROP password');
        $this->addSql('ALTER TABLE users DROP google_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "users" ADD password VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "users" ADD google_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "users" DROP is_owner');
        $this->addSql('CREATE UNIQUE INDEX uniq_1483a5e976f5c865 ON "users" (google_id)');
    }
}
