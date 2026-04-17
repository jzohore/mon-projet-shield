<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260416225253 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE click_logs (id UUID NOT NULL, slug_id VARCHAR(255) NOT NULL, element_name VARCHAR(100) NOT NULL, page_url TEXT NOT NULL, referrer TEXT DEFAULT NULL, user_agent TEXT DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, resolution VARCHAR(20) DEFAULT NULL, locale VARCHAR(10) DEFAULT NULL, utm_source VARCHAR(100) DEFAULT NULL, utm_medium VARCHAR(100) DEFAULT NULL, utm_campaign VARCHAR(100) DEFAULT NULL, session_id VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1F4CF69E311966CE ON click_logs (slug_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE click_logs');
    }
}
