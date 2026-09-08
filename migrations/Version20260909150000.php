<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Durcissement des invitations :
 *  - suppression de l'unicité GLOBALE sur l'e-mail (empêchait de réinviter et
 *    qu'un e-mail soit invité par deux cabinets) ; l'unicité « une invitation en
 *    attente par (cabinet, e-mail) » est assurée applicativement ;
 *  - le jeton n'est plus stocké en clair (255) mais haché en SHA-256 (64).
 */
final class Version20260909150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Invitations : e-mail non unique globalement + jeton haché';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_47a29ea2e7927c74');
        // Les jetons en clair existants ne correspondront plus à leur hachage :
        // on les invalide AVANT de réduire la colonne (aucune invitation en cours
        // au moment du déploiement).
        $this->addSql('UPDATE workspaces_invitations SET magic_link_token = NULL, magic_link_token_expires_at = NULL WHERE magic_link_token IS NOT NULL');
        $this->addSql('ALTER TABLE workspaces_invitations ALTER magic_link_token TYPE VARCHAR(64)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workspaces_invitations ALTER magic_link_token TYPE VARCHAR(255)');
        $this->addSql('CREATE UNIQUE INDEX uniq_47a29ea2e7927c74 ON workspaces_invitations (email)');
    }
}
