<?php

declare(strict_types=1);

namespace App\Domain\Billing\Enum;

use App\Domain\Workspace\Enum\WorkspaceType;

/**
 * Offres KYSURE. Modèle : abonnement AU SIÈGE, dossiers de conformité illimités
 * (fair-use), seules les minutes d'entretien sont métrées (incluses + packs).
 *
 * Les prix et l'ID de prix Stripe vivent dans la conf d'environnement
 * (clés `STRIPE_PRICE_*`) : ici on ne garde que la structure commerciale,
 * consultable côté admin.
 */
enum Plan: string
{
    case INDIVIDUAL = 'individual';
    case CABINET = 'cabinet';

    public static function forWorkspaceType(WorkspaceType $type): self
    {
        return WorkspaceType::FIRM === $type ? self::CABINET : self::INDIVIDUAL;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::INDIVIDUAL => 'Indépendant',
            self::CABINET => 'Cabinet',
        };
    }

    /** Prix mensuel par siège, en centimes (prix de lancement). */
    public function getMonthlyPriceCentsPerSeat(): int
    {
        return match ($this) {
            self::INDIVIDUAL => 9900,
            self::CABINET => 7900,
        };
    }

    /** Nombre de sièges minimum facturables. */
    public function getMinSeats(): int
    {
        return match ($this) {
            self::INDIVIDUAL => 1,
            self::CABINET => 2,
        };
    }

    /** Minutes d'entretien incluses par siège et par cycle de facturation. */
    public function getIncludedMinutesPerSeat(): int
    {
        return match ($this) {
            self::INDIVIDUAL, self::CABINET => 150,
        };
    }

    /** Clé de la variable d'env portant l'ID de prix Stripe (par siège). */
    public function getStripePriceEnvKey(): string
    {
        return match ($this) {
            self::INDIVIDUAL => 'STRIPE_PRICE_INDIVIDUAL_SEAT',
            self::CABINET => 'STRIPE_PRICE_CABINET_SEAT',
        };
    }

    public function allowsCollaborators(): bool
    {
        return self::CABINET === $this;
    }
}
