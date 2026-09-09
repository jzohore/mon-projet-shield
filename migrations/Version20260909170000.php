<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Invitations collaborateur : index sur le jeton (lookup sur route publique) et
 * garde-fou d'unicité « une invitation en attente par (cabinet, e-mail) ».
 */
final class Version20260909170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'workspaces_invitations : index magic_link_token + unique partiel (workspace_id, email) WHERE pending';
    }

    public function up(Schema $schema): void
    {
        // Le lookup par jeton se fait sur /invitation/confirm/{token}, route publique
        // non authentifiée : sans index c'est un seq scan à chaque hit.
        $this->addSql('CREATE INDEX idx_wrk_inv_magic_token ON workspaces_invitations (magic_link_token)');

        // Dédoublonnage défensif avant la contrainte : on garde la plus récente.
        $this->addSql(<<<'SQL'
            DELETE FROM workspaces_invitations wi
            USING workspaces_invitations keep
            WHERE wi.invitation_status = 'pending'
              AND keep.invitation_status = 'pending'
              AND wi.workspace_id = keep.workspace_id
              AND wi.email = keep.email
              AND wi.created_at < keep.created_at
            SQL);

        // Index unique partiel : ferme la fenêtre TOCTOU entre le contrôle
        // hasPendingInvitation() et l'insertion. Doctrine ORM ne modélise pas la
        // clause WHERE : doctrine:schema:validate signalera cet index comme « en
        // trop », c'est attendu et documenté.
        $this->addSql("CREATE UNIQUE INDEX uniq_wrk_inv_pending ON workspaces_invitations (workspace_id, email) WHERE invitation_status = 'pending'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_wrk_inv_pending');
        $this->addSql('DROP INDEX idx_wrk_inv_magic_token');
    }
}
