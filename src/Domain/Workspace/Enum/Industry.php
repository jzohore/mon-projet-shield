<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Enum;

namespace App\Domain\Workspace\Enum;

enum Industry: string
{
    case LAWYER = 'lawyer';
    case REAL_ESTATE = 'real_estate';
    case ACCOUNTANT = 'accountant';
    case WEALTH_MANAGEMENT = 'wealth_management'; // Gestion de patrimoine
    case OTHER = 'other';

    /**
     * Retourne le libellé formaté pour l'affichage dans les formulaires Twig.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::LAWYER => 'Avocat / Cabinet juridique',
            self::REAL_ESTATE => 'Agence immobilière',
            self::ACCOUNTANT => 'Expert-comptable',
            self::WEALTH_MANAGEMENT => 'Conseiller en gestion de patrimoine',
            self::OTHER => 'Autre secteur',
        };
    }

    /**
     * 🪄 NOUVEAU : Déduit le secteur d'activité métier à partir du code APE/NAF.
     */
    public static function fromApeCode(?string $apeCode): self
    {
        if (!$apeCode) {
            return self::OTHER;
        }

        // On nettoie le code pour être robuste (ex: "69.10Z" ou "6910Z" -> "6910Z")
        $cleanApe = strtoupper(str_replace('.', '', trim($apeCode)));

        return match (true) {
            // 69.10Z : Activités juridiques
            str_starts_with($cleanApe, '6910') => self::LAWYER,

            // 68.31Z : Agences immobilières / 68.32A : Syndics et administration de biens
            str_starts_with($cleanApe, '683') => self::REAL_ESTATE,

            // 69.20Z : Activités comptables
            str_starts_with($cleanApe, '6920') => self::ACCOUNTANT,

            // Gestion de patrimoine (Souvent immatriculés comme courtiers, conseil de gestion ou conseil financier)
            in_array($cleanApe, ['6622Z', '7022Z', '6619B'], true) => self::WEALTH_MANAGEMENT,

            // Si le code APE ne correspond à aucune de nos cibles RegTech principales
            default => self::OTHER,
        };
    }
}
