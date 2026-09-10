<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Retrait du tracking maison (ClickLog). L'analytique passe désormais par Umami
 * self-hosted (cf. compose.yaml). Aucune donnée applicative n'est concernée.
 */
final class Version20260911090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop click_logs (analytique déléguée à Umami)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS click_logs');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE click_logs (
                id UUID NOT NULL,
                slug_id VARCHAR(255) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                element_name VARCHAR(100) NOT NULL,
                page_url TEXT NOT NULL,
                referrer TEXT DEFAULT NULL,
                user_agent TEXT DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                resolution VARCHAR(20) DEFAULT NULL,
                locale VARCHAR(10) DEFAULT NULL,
                utm_source VARCHAR(100) DEFAULT NULL,
                utm_medium VARCHAR(100) DEFAULT NULL,
                utm_campaign VARCHAR(100) DEFAULT NULL,
                session_id VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_click_logs_slug_id ON click_logs (slug_id)');
        $this->addSql('CREATE INDEX idx_click_created_at ON click_logs (created_at)');
        $this->addSql('CREATE INDEX idx_click_element_name ON click_logs (element_name)');
    }
}
