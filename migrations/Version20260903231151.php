<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Webhook DocuSeal : gardes d'idempotence `form.viewed` / `form.declined` +
 * unicité de la soumission DocuSeal (protège le scellement de preuve contre un
 * rattachement au mauvais dossier et un double traitement concurrent).
 *
 * Additive : colonnes nullables, aucun backfill.
 */
final class Version20260903231151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'DocuSeal webhook : docu_seal_opened_at / declined_at + index unique docu_seal_submission_id';
    }

    public function up(Schema $schema): void
    {
        // Un doublon de docu_seal_submission_id non-NULL (scénario du double-clic
        // d'envoi) ferait échouer le CREATE UNIQUE INDEX. On refuse la migration
        // plutôt que de trancher automatiquement quel DER signé conserver.
        $duplicates = (int) $this->connection->fetchOne(
            <<<'SQL'
                SELECT COUNT(*) FROM (
                    SELECT docu_seal_submission_id
                    FROM compliance_documents
                    WHERE docu_seal_submission_id IS NOT NULL
                    GROUP BY docu_seal_submission_id
                    HAVING COUNT(*) > 1
                ) AS d
                SQL
        );

        $this->abortIf(
            $duplicates > 0,
            sprintf(
                'Migration interrompue : %d valeur(s) docu_seal_submission_id en doublon dans compliance_documents. '
                . 'Résoudre manuellement (données à valeur juridique) avant de rejouer cette migration.',
                $duplicates
            )
        );

        $this->addSql('ALTER TABLE compliance_documents ADD docu_seal_opened_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE compliance_documents ADD docu_seal_declined_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_doc_docuseal_submission ON compliance_documents (docu_seal_submission_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_doc_docuseal_submission');
        $this->addSql('ALTER TABLE compliance_documents DROP docu_seal_opened_at');
        $this->addSql('ALTER TABLE compliance_documents DROP docu_seal_declined_at');
    }
}
