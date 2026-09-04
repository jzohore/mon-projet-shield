<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Bascule de l'envoi du DER vers l'accusé de réception : suivi de la demande
 * d'envoi (der_send_requested_at) et de l'envoi effectif du lien (der_link_sent_at).
 * Additive, colonnes nullables.
 */
final class Version20260904091441 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'DER : suivi de la demande d\'envoi et de l\'envoi du lien d\'accusé de réception';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE compliance_documents ADD der_send_requested_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE compliance_documents ADD der_link_sent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE compliance_documents DROP der_send_requested_at');
        $this->addSql('ALTER TABLE compliance_documents DROP der_link_sent_at');
    }
}
