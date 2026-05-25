<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260515130806 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users RENAME COLUMN dismiss_onboarding TO profile_dismiss_onboarding');
        $this->addSql('ALTER TABLE users RENAME COLUMN stripe_customer_id TO profile_stripe_customer_id');
        $this->addSql('ALTER TABLE users RENAME COLUMN lang TO profile_lang');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "users" RENAME COLUMN profile_lang TO lang');
        $this->addSql('ALTER TABLE "users" RENAME COLUMN profile_dismiss_onboarding TO dismiss_onboarding');
        $this->addSql('ALTER TABLE "users" RENAME COLUMN profile_stripe_customer_id TO stripe_customer_id');
    }
}
