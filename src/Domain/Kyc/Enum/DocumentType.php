<?php

namespace App\Domain\Kyc\Enum;

enum DocumentType: string
{
    // ==========================================
    // 🙎‍♂️ KYC - IDENTITÉ & PARTICULIERS
    // ==========================================
    case ID_CARD = 'id_card';
    case PASSPORT = 'passport';
    case RESIDENCE_PERMIT = 'residence_permit';
    case PROOF_OF_ADDRESS = 'proof_of_address';

    // ==========================================
    // 🏢 KYB - ENTREPRISES & PERSONNES MORALES
    // ==========================================
    case KBIS = 'kbis';
    case ARTICLES_OF_ASSOC = 'articles_of_assoc';
    case RBE = 'rbe'; // Registre des Bénéficiaires Effectifs
    case ORGANIZATION_CHART = 'organization_chart'; // Obligatoire si structure complexe
    case FINANCIAL_STATEMENTS = 'financial_statements'; // Liasses fiscales

    // ==========================================
    // 🛡️ LCB-FT SPÉCIFIQUE (Vigilance Renforcée)
    // ==========================================
    case SOURCE_OF_FUNDS = 'source_of_funds'; // Justificatif d'origine des fonds
    case RIB = 'rib';

    /**
     * Retourne le libellé propre pour l'affichage (Agent et Client)
     */
    public function getLabel(): string
    {
        return match ($this) {
            // KYC
            self::ID_CARD => 'Carte Nationale d\'Identité (Recto/Verso)',
            self::PASSPORT => 'Passeport (Page biométrique)',
            self::RESIDENCE_PERMIT => 'Titre de séjour en cours de validité',
            self::PROOF_OF_ADDRESS => 'Justificatif de domicile (moins de 3 mois)',

            // KYB
            self::KBIS => 'Extrait K-bis (moins de 3 mois)',
            self::ARTICLES_OF_ASSOC => 'Statuts constitutifs à jour et signés',
            self::RBE => 'Déclaration des Bénéficiaires Effectifs (UBO)',
            self::ORGANIZATION_CHART => 'Organigramme capitalistique daté et signé',
            self::FINANCIAL_STATEMENTS => 'Dernière liasse fiscale / Bilan',

            // LCB-FT / Risque
            self::SOURCE_OF_FUNDS => 'Justificatif d\'origine des fonds',
            self::RIB => 'Relevé d\'Identité Bancaire (RIB)',
        };
    }

    /**
     * 💡 Bonus Architecte : Une méthode pour aider ton moteur de règles plus tard
     */
    public function isHighRiskDocument(): bool
    {
        return match ($this) {
            self::SOURCE_OF_FUNDS, self::ORGANIZATION_CHART => true,
            default => false,
        };
    }
}
