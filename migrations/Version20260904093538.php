<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Refus du DER par le client (der_declined_at / der_decline_reason). Additive.
 */
final class Version20260904093538 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'DER : refus du client (der_declined_at, der_decline_reason)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE compliance_documents ADD der_declined_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE compliance_documents ADD der_decline_reason TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE compliance_documents DROP der_declined_at');
        $this->addSql('ALTER TABLE compliance_documents DROP der_decline_reason');
    }
}
