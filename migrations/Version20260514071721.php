<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260514071721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE compliance_folder_restricted_users (compliance_folder_id UUID NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (compliance_folder_id, user_id))');
        $this->addSql('CREATE INDEX IDX_72EC667C5E22A3B9 ON compliance_folder_restricted_users (compliance_folder_id)');
        $this->addSql('CREATE INDEX IDX_72EC667CA76ED395 ON compliance_folder_restricted_users (user_id)');
        $this->addSql('ALTER TABLE compliance_folder_restricted_users ADD CONSTRAINT FK_72EC667C5E22A3B9 FOREIGN KEY (compliance_folder_id) REFERENCES compliance_folders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE compliance_folder_restricted_users ADD CONSTRAINT FK_72EC667CA76ED395 FOREIGN KEY (user_id) REFERENCES "users" (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE compliance_documents DROP CONSTRAINT fk_eabe6873162cb942');
        $this->addSql('ALTER TABLE compliance_documents ADD CONSTRAINT FK_EABE6873162CB942 FOREIGN KEY (folder_id) REFERENCES compliance_folders (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE compliance_folders ADD is_confidential BOOLEAN NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE compliance_folder_restricted_users DROP CONSTRAINT FK_72EC667C5E22A3B9');
        $this->addSql('ALTER TABLE compliance_folder_restricted_users DROP CONSTRAINT FK_72EC667CA76ED395');
        $this->addSql('DROP TABLE compliance_folder_restricted_users');
        $this->addSql('ALTER TABLE compliance_documents DROP CONSTRAINT FK_EABE6873162CB942');
        $this->addSql('ALTER TABLE compliance_documents ADD CONSTRAINT fk_eabe6873162cb942 FOREIGN KEY (folder_id) REFERENCES kyc_folders (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE compliance_folders DROP is_confidential');
    }
}
