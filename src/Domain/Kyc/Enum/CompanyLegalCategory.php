<?php

declare(strict_types=1);

namespace App\Domain\Kyc\Enum;

enum CompanyLegalCategory: string
{
    // --- 1. PERSONNES PHYSIQUES ---
    case EI = '1000'; // Entrepreneur individuel / Micro-entreprise

    // --- 5. SOCIÉTÉS COMMERCIALES ---
    case SNC = '5202'; // Société en nom collectif
    case EURL = '5485'; // SARL unipersonnelle
    case SARL = '5498'; // Société à responsabilité limitée
    case SA = '5599'; // Société anonyme
    case SAS = '5710'; // Société par actions simplifiée
    case SASU = '5720'; // SAS unipersonnelle

    // --- 6. SOCIÉTÉS CIVILES ---
    case SCI = '6540'; // Société civile immobilière
    case SCP = '6585'; // Société civile professionnelle

    // --- 9. ASSOCIATIONS ---
    case ASSOCIATION = '9220'; // Association déclarée

    // --- CAS SPÉCIFIQUES (Notre logique interne) ---
    case IN_FORMATION = 'IN_FORMATION'; // Société en cours de création

    /**
     * Obtenir un libellé humain pour l'interface (si besoin).
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::EI => 'Entrepreneur Individuel',
            self::SNC => 'SNC',
            self::EURL => 'EURL',
            self::SARL => 'SARL',
            self::SA => 'SA',
            self::SAS => 'SAS',
            self::SASU => 'SASU',
            self::SCI => 'SCI',
            self::SCP => 'SCP',
            self::ASSOCIATION => 'Association',
            self::IN_FORMATION => 'En cours d\'immatriculation',
        };
    }

    /**
     * 🧠 RÈGLE MÉTIER 1 : Est-ce une société avec des actionnaires/associés ?
     * (Détermine si on doit afficher l'Étape 2 pour ajouter les Bénéficiaires).
     */
    public function requiresUboDeclaration(): bool
    {
        return match ($this) {
            // Un auto-entrepreneur ou une asso n'a pas d'actionnaires (au sens LCB-FT classique)
            self::EI, self::ASSOCIATION => false,
            // Toutes les autres sociétés commerciales/civiles et celles en création oui
            default => true,
        };
    }

    /**
     * 🧠 RÈGLE MÉTIER 2 : A-t-on besoin d'un Kbis ?
     */
    public function requiresKbis(): bool
    {
        return match ($this) {
            self::IN_FORMATION => false, // Impossible, elle n'est pas créée !
            self::EI => false, // On demandera un Avis Sirene
            self::ASSOCIATION => false, // On demandera un Récépissé
            default => true, // Les SAS, SARL, SCI... ont un Kbis
        };
    }

    /**
     * 🧠 RÈGLE MÉTIER 3 : A-t-on besoin des statuts de l'entreprise ?
     */
    public function requiresStatutes(): bool
    {
        return match ($this) {
            self::EI => false, // Un freelance n'a pas de statuts
            default => true, // Tout le reste (même en création = "Projet de statuts") en a besoin
        };
    }
}
