<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407152129 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE "products" (id UUID NOT NULL, name VARCHAR(255) NOT NULL, slug_id VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, credits INT NOT NULL, price_in_cents INT NOT NULL, stripe_price_id VARCHAR(255) NOT NULL, is_recommended BOOLEAN DEFAULT false NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B3BA5A5A311966CE ON "products" (slug_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B3BA5A5A8B531BD4 ON "products" (stripe_price_id)');
        $this->addSql('ALTER TABLE users ALTER dismiss_onboarding SET DEFAULT false');
        $this->addSql('ALTER TABLE users ALTER dismiss_onboarding SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE "products"');
        $this->addSql('ALTER TABLE "users" ALTER dismiss_onboarding DROP DEFAULT');
        $this->addSql('ALTER TABLE "users" ALTER dismiss_onboarding DROP NOT NULL');
    }
}
