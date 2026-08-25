<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Enum;

enum ComplianceFolderStatus: string
{
    case DRAFT = 'draft';

    case DER_GENERATED = 'der_generated';
    case DER_SENT = 'der_sent';
    case DER_SIGNED = 'der_signed';
    case DER_REJECTED = 'der_rejected';

    case DER_OPENED = 'der_opened';
    case AWAITING_CLIENT = 'awaiting_client';
    case IN_REVIEW = 'in_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case ARCHIVED = 'archived';
    case DELETED = 'deleted';
    case NEEDS_CORRECTION = 'needs_correction';
    case PENDING_DOCS = 'pending_docs';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',

            // Cycle de vie du DER
            self::DER_GENERATED => 'DER prêt à l\'envoi', // Plus orienté action que "généré"
            self::DER_SENT => 'En attente de signature',
            self::DER_OPENED => 'DER consulté', // Fait plus pro que "ouvert"
            self::DER_SIGNED => 'DER signé',
            self::DER_REJECTED => 'DER refusé',

            // Cycle de vie KYC / Conformité
            self::AWAITING_CLIENT => 'Action requise (Client)', // Ultra clair pour le CGP
            self::PENDING_DOCS => 'Pièces manquantes', // Crée un sentiment d'urgence
            self::IN_REVIEW => 'En cours d\'analyse',
            self::NEEDS_CORRECTION => 'Corrections requises',

            // États finaux (Jargon Compliance)
            self::APPROVED => 'Conforme', // Le mot magique pour un CGP face à l'AMF
            self::REJECTED => 'Non conforme',
            self::ARCHIVED => 'Archivé',
            self::DELETED => 'Supprimé',
        };
    }

    public function getBadgeClasses(): string
    {
        $baseClasses = 'inline-flex items-center px-2 py-1 rounded-md text-xs font-medium ring-1 ring-inset ';

        return $baseClasses . match ($this) {
            // Gris (États neutres ou de départ)
            self::DRAFT, self::ARCHIVED, self::DELETED => 'bg-slate-50 text-slate-600 ring-slate-500/10',

            // Bleu (Information / Progression)
            self::DER_GENERATED, self::DER_OPENED => 'bg-blue-50 text-blue-700 ring-blue-700/10',

            // Jaune / Orange (Attente ou action requise)
            self::DER_SENT, self::AWAITING_CLIENT, self::PENDING_DOCS, self::IN_REVIEW => 'bg-amber-50 text-amber-700 ring-amber-600/20',

            // Rouge (Blocage / Refus)
            self::DER_REJECTED, self::NEEDS_CORRECTION, self::REJECTED => 'bg-red-50 text-red-700 ring-red-600/10',

            // Vert (Succès juridique)
            self::DER_SIGNED, self::APPROVED => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        };
    }

    /**
     * @return array<self>
     */
    public static function getActiveStatuses(): array
    {
        return [
            self::AWAITING_CLIENT,
            self::IN_REVIEW,
            self::APPROVED,
            self::NEEDS_CORRECTION,
        ];
    }

    /**
     * @return array<self>
     */
    public static function getKycPhaseStatuses(): array
    {
        return [
            // Le point de bascule : le DER est signé, le KYC commence
            self::DER_SIGNED,

            // Phase d'action client
            self::AWAITING_CLIENT,
            self::PENDING_DOCS,
            self::NEEDS_CORRECTION,

            // Phase d'analyse CGP
            self::IN_REVIEW,

            // États finaux
            self::APPROVED,
            self::REJECTED,
            self::ARCHIVED,
        ];
    }
}
