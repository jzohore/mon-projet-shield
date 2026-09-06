<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

/**
 * Motif structuré du retrait d'un client du portefeuille d'un cabinet (qu'il
 * s'agisse d'un simple détachement ou d'une suppression de compte). Liste fermée :
 * le journal d'audit inaltérable ne porte jamais de texte libre.
 */
enum ClientRemovalReason: string
{
    case CREATION_ERRONEE = 'creation_erronee';
    case DOUBLON = 'doublon';
    case JAMAIS_ENTRE_EN_RELATION = 'jamais_entre_en_relation';
    case FIN_COLLABORATION = 'fin_collaboration';
    case AUTRE = 'autre';

    public function getLabel(): string
    {
        return match ($this) {
            self::CREATION_ERRONEE => 'Création erronée',
            self::DOUBLON => 'Doublon',
            self::JAMAIS_ENTRE_EN_RELATION => 'Jamais entré en relation',
            self::FIN_COLLABORATION => 'Fin de collaboration',
            self::AUTRE => 'Autre',
        };
    }
}
