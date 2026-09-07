<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fondations de la couche commerciale (quota & abonnement) :
 *  - nombre de sièges facturés sur l'abonnement Stripe ;
 *  - compteur de dossiers d'essai gratuit sur le workspace (ignoré une fois abonné).
 * Additives, non nullables avec valeur par défaut.
 */
final class Version20260908120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Billing : seats_count sur subscriptions + trial_dossiers_remaining sur workspaces';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE subscriptions ADD seats_count INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE workspaces ADD trial_dossiers_remaining INT DEFAULT 5 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE subscriptions DROP seats_count');
        $this->addSql('ALTER TABLE workspaces DROP trial_dossiers_remaining');
    }
}
