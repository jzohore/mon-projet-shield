<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Enum;

/**
 * Motif structuré de fin de relation d'affaires. Liste fermée : on n'écrit
 * jamais de texte libre dans le journal d'audit (inaltérable), et un motif de
 * type « soupçon » ne doit pas devenir une trace divulgable (art. L.561-18 CMF).
 */
enum RelationshipEndReason: string
{
    case FIN_DE_MANDAT = 'fin_de_mandat';
    case DEPART_CLIENT = 'depart_client';
    case NON_REPONSE_PROLONGEE = 'non_reponse_prolongee';
    case DEMANDE_CLIENT = 'demande_client';
    case RISQUE_LCBFT = 'risque_lcbft';

    public function getLabel(): string
    {
        return match ($this) {
            self::FIN_DE_MANDAT => 'Fin de mandat',
            self::DEPART_CLIENT => 'Départ du client',
            self::NON_REPONSE_PROLONGEE => 'Absence de réponse prolongée',
            self::DEMANDE_CLIENT => 'À la demande du client',
            self::RISQUE_LCBFT => 'Risque LCB-FT',
        };
    }

    /**
     * Ce motif ne doit jamais apparaître dans un contenu remis au client
     * (e-mail, portail, PDF).
     */
    public function isConfidential(): bool
    {
        return self::RISQUE_LCBFT === $this;
    }
}
