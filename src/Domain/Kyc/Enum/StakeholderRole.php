<?php

namespace App\Domain\Kyc\Enum;

enum StakeholderRole: string
{
    case DIRECTOR = 'director'; // Mandataire social (Directeur, Membre du CA...)
    case BENEFICIAL_OWNER = 'beneficial_owner'; // Actionnaire > 25%
    case LEGAL_REPRESENTATIVE = 'legal_rep'; // Celui qui a la signature (Président, Gérant...)

    public function getLabel(): string
    {
        return match ($this) {
            self::DIRECTOR => 'Dirigeant / Mandataire social',
            self::BENEFICIAL_OWNER => 'Bénéficiaire Effectif (>25%)',
            self::LEGAL_REPRESENTATIVE => 'Représentant Légal',
        };
    }

    /**
     * 🧠 LE TRADUCTEUR API -> ENUM INTERNE
     * Analyse la "qualité" renvoyée par Pappers/INSEE et retourne le bon rôle KYC.
     */
    public static function fromApiRole(string $qualite): self
    {
        // On passe tout en minuscules pour faciliter la recherche
        $qualiteLower = mb_strtolower($qualite, 'UTF-8');

        // 1. Les Représentants Légaux (Ceux qui signent)
        if (
            str_contains($qualiteLower, 'gérant')
            || str_contains($qualiteLower, 'président')
            || str_contains($qualiteLower, 'directeur général')
        ) {
            return self::LEGAL_REPRESENTATIVE;
        }

        // 2. Les Dirigeants simples (Membres du conseil, directeurs non-généraux...)
        if (
            str_contains($qualiteLower, 'directeur')
            || str_contains($qualiteLower, 'administrateur')
            || str_contains($qualiteLower, 'membre')
        ) {
            return self::DIRECTOR;
        }

        // 3. Cas par défaut : On le met en Dirigeant simple si on ne sait pas
        return self::DIRECTOR;
    }
}
