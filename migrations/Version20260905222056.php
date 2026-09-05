<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260905222056 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'RGPD : horodatage de purge IP/UA des accusés DER + minimisation des résultats bruts de screening';
    }

    public function up(Schema $schema): void
    {
        // Additif : colonnes nullables.
        $this->addSql('ALTER TABLE compliance_der_acknowledgement ADD technical_data_purged_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE screening_audits ADD results_digest VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE screening_audits ADD results_minimized_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE compliance_der_acknowledgement DROP technical_data_purged_at');
        $this->addSql('ALTER TABLE screening_audits DROP results_digest');
        $this->addSql('ALTER TABLE screening_audits DROP results_minimized_at');
    }
}
