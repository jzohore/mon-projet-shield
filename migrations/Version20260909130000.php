<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Offres commerciales KYSURE persistées (prix + identifiants Stripe en base,
 * pour survivre à un reset ; re-provisionnées par app:billing:sync-pricing).
 */
final class Version20260909130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Billing : table pricing_plans';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE pricing_plans (
                id UUID NOT NULL,
                slug_id VARCHAR(64) NOT NULL,
                plan_key VARCHAR(64) NOT NULL,
                kind VARCHAR(30) NOT NULL,
                label VARCHAR(120) NOT NULL,
                unit_amount_cents INT NOT NULL,
                meeting_minutes INT DEFAULT 0 NOT NULL,
                min_seats INT DEFAULT 1 NOT NULL,
                stripe_product_id VARCHAR(255) DEFAULT NULL,
                stripe_price_id VARCHAR(255) DEFAULT NULL,
                is_active BOOLEAN DEFAULT true NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY(id)
            )
            SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_19951050311966CE ON pricing_plans (slug_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_19951050867110F4 ON pricing_plans (plan_key)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_199510508B531BD4 ON pricing_plans (stripe_price_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE pricing_plans');
    }
}
