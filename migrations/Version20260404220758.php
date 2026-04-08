<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404220758 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE wallet_transactions (id UUID NOT NULL, amount INT NOT NULL, type VARCHAR(255) DEFAULT NULL, reference_id VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, workspace_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_A50205E282D40A1F ON wallet_transactions (workspace_id)');
        $this->addSql('ALTER TABLE wallet_transactions ADD CONSTRAINT FK_A50205E282D40A1F FOREIGN KEY (workspace_id) REFERENCES workspaces (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE workspaces RENAME COLUMN workspace_balance TO balance');
        $this->addSql('ALTER TABLE workspaces RENAME COLUMN workspace_transactions TO transactions');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wallet_transactions DROP CONSTRAINT FK_A50205E282D40A1F');
        $this->addSql('DROP TABLE wallet_transactions');
        $this->addSql('ALTER TABLE workspaces RENAME COLUMN balance TO workspace_balance');
        $this->addSql('ALTER TABLE workspaces RENAME COLUMN transactions TO workspace_transactions');
    }
}
