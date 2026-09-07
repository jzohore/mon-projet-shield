<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Idempotence des webhooks Stripe : table des événements déjà traités.
 */
final class Version20260909120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Billing : table processed_stripe_events (idempotence webhooks Stripe)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE processed_stripe_events (event_id VARCHAR(255) NOT NULL, processed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(event_id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE processed_stripe_events');
    }
}
