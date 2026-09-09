<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Invitations collaborateur : horodatage du dernier renvoi, pour le compte à
 * rebours anti-spam du bouton « Renvoyer ».
 */
final class Version20260909180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'workspaces_invitations.last_resent_at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workspaces_invitations ADD last_resent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workspaces_invitations DROP last_resent_at');
    }
}
