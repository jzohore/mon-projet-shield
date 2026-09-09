<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Empreinte d'identité de session (tâche C1) : régénérée à la révocation d'un
 * membre, elle invalide ses sessions ouvertes au prochain rafraîchissement.
 */
final class Version20260909200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'users.security_stamp';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE \"users\" ADD security_stamp VARCHAR(32) DEFAULT '' NOT NULL");
        // Empreinte initiale unique par compte (md5 = 32 hex).
        $this->addSql('UPDATE "users" SET security_stamp = substr(md5(random()::text || id::text || clock_timestamp()::text), 1, 32)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "users" DROP security_stamp');
    }
}
