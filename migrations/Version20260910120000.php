<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Équipe KYSURE : cycle de vie des comptes du back-office.
 * - security_stamp : coupe les sessions ouvertes à la suspension / au changement de rôle / à l'archivage.
 * - suspended_at / archived_at : états explicites du compte.
 */
final class Version20260910120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'admins.security_stamp / suspended_at / archived_at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE \"admins\" ADD security_stamp VARCHAR(32) DEFAULT '' NOT NULL");
        $this->addSql('ALTER TABLE "admins" ADD suspended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE "admins" ADD archived_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        // Empreinte initiale unique par compte (md5 = 32 hex).
        $this->addSql('UPDATE "admins" SET security_stamp = substr(md5(random()::text || id::text || clock_timestamp()::text), 1, 32)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "admins" DROP security_stamp');
        $this->addSql('ALTER TABLE "admins" DROP suspended_at');
        $this->addSql('ALTER TABLE "admins" DROP archived_at');
    }
}
