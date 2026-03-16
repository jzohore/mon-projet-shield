<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

/**
 * Référentiel des rôles et métiers (Cible RegTech / B2B)
 */
enum JobRole: string
{
    // --- Conformité & Risques ---
    case COMPLIANCE_OFFICER = 'compliance_officer';
    case RISK_DIRECTOR = 'risk_director';
    case KYC_ANALYST = 'kyc_analyst';
    case INTERNAL_AUDITOR = 'internal_auditor';

    // --- Direction ---
    case CEO = 'ceo';
    case PARTNER = 'partner';
    case GENERAL_MANAGER = 'general_manager';

    // --- Professions Réglementées ---
    case LAWYER = 'lawyer';
    case ACCOUNTANT = 'accountant';
    case NOTARY = 'notary';
    case REAL_ESTATE_AGENT = 'real_estate_agent';

    // --- Support & Opérationnel ---
    case LEGAL_ASSISTANT = 'legal_assistant';
    case OFFICE_MANAGER = 'office_manager';
    case CONSULTANT = 'consultant';
    case OTHER = 'other';

    /**
     * Retourne le nom du métier formaté pour l'affichage (UI).
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::COMPLIANCE_OFFICER => 'Responsable Conformité (Déclarant Tracfin)',
            self::RISK_DIRECTOR => 'Directeur des Risques',
            self::KYC_ANALYST => 'Analyste KYC / LCB-FT',
            self::INTERNAL_AUDITOR => 'Auditeur Interne',

            self::CEO => 'Dirigeant / Fondateur',
            self::PARTNER => 'Associé (Partner)',
            self::GENERAL_MANAGER => 'Directeur Général',

            self::LAWYER => 'Avocat',
            self::ACCOUNTANT => 'Expert-Comptable',
            self::NOTARY => 'Notaire',
            self::REAL_ESTATE_AGENT => 'Agent / Mandataire Immobilier',

            self::LEGAL_ASSISTANT => 'Assistant(e) Juridique',
            self::OFFICE_MANAGER => 'Office Manager',
            self::CONSULTANT => 'Consultant(e)',
            self::OTHER => 'Autre',
        };
    }

    /**
     * Génère un tableau parfaitement formaté pour un ChoiceType Symfony
     * avec les <optgroup> par catégories métier !
     */
    public static function getGroupedChoices(): array
    {
        return [
            'Conformité & Risques' => [
                self::COMPLIANCE_OFFICER->getLabel() => self::COMPLIANCE_OFFICER,
                self::KYC_ANALYST->getLabel() => self::KYC_ANALYST,
                self::RISK_DIRECTOR->getLabel() => self::RISK_DIRECTOR,
                self::INTERNAL_AUDITOR->getLabel() => self::INTERNAL_AUDITOR,
            ],
            'Direction & Décisionnaires' => [
                self::CEO->getLabel() => self::CEO,
                self::PARTNER->getLabel() => self::PARTNER,
                self::GENERAL_MANAGER->getLabel() => self::GENERAL_MANAGER,
            ],
            'Professions Réglementées' => [
                self::LAWYER->getLabel() => self::LAWYER,
                self::ACCOUNTANT->getLabel() => self::ACCOUNTANT,
                self::NOTARY->getLabel() => self::NOTARY,
                self::REAL_ESTATE_AGENT->getLabel() => self::REAL_ESTATE_AGENT,
            ],
            'Opérationnel & Support' => [
                self::LEGAL_ASSISTANT->getLabel() => self::LEGAL_ASSISTANT,
                self::OFFICE_MANAGER->getLabel() => self::OFFICE_MANAGER,
                self::CONSULTANT->getLabel() => self::CONSULTANT,
                self::OTHER->getLabel() => self::OTHER,
            ],
        ];
    }
}
