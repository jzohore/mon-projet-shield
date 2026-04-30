<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260424142637 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE subscriptions (id UUID NOT NULL, status VARCHAR(255) NOT NULL, plan_reference VARCHAR(255) NOT NULL, stripe_subscription_id VARCHAR(255) NOT NULL, stripe_price_id VARCHAR(255) NOT NULL, current_period_start TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, current_period_end TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, cancel_at_period_end BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, trial_ends_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, workspace_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4778A01B5DBB761 ON subscriptions (stripe_subscription_id)');
        $this->addSql('CREATE INDEX IDX_4778A0182D40A1F ON subscriptions (workspace_id)');
        $this->addSql('ALTER TABLE subscriptions ADD CONSTRAINT FK_4778A0182D40A1F FOREIGN KEY (workspace_id) REFERENCES workspaces (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subscriptions DROP CONSTRAINT FK_4778A0182D40A1F');
        $this->addSql('DROP TABLE subscriptions');
    }
}
