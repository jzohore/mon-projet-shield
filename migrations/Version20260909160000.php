<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Abonnement : suspension (pause_collection Stripe) + offre de fidélité
 * utilisable une seule fois au moment d'une tentative de résiliation.
 */
final class Version20260909160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Billing : subscriptions.paused_at + retention_offer_claimed_at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE subscriptions ADD paused_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE subscriptions ADD retention_offer_claimed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE subscriptions DROP paused_at');
        $this->addSql('ALTER TABLE subscriptions DROP retention_offer_claimed_at');
    }
}
