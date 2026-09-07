<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Champs manquants sur le DER :
 *  - adresse postale du client (dossier physique) ;
 *  - conseiller signataire + ville « Fait à … » (profil réglementaire du cabinet).
 * Toutes additives et nullables.
 */
final class Version20260907120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'DER : adresse client + conseiller signataire et ville « Fait à »';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE compliance_folders ADD address VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE regulatory_profiles ADD signatory_name VARCHAR(150) DEFAULT NULL');
        $this->addSql('ALTER TABLE regulatory_profiles ADD signatory_city VARCHAR(120) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE compliance_folders DROP address');
        $this->addSql('ALTER TABLE regulatory_profiles DROP signatory_name');
        $this->addSql('ALTER TABLE regulatory_profiles DROP signatory_city');
    }
}
