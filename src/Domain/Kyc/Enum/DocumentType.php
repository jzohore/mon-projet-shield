<?php

namespace App\Domain\Kyc\Enum;

enum DocumentType: string
{
    case KBIS = 'kbis';
    case RBE = 'rbe';
    case ARTICLES_OF_ASSOC = 'articles';
    case ID_CARD = 'id_card';
    case PASSPORT = 'passport';

    public function getLabel(): string
    {
        return match ($this) {
            self::KBIS => 'Extrait Kbis (moins de 3 mois)',
            self::RBE => 'Registre des Bénéficiaires Effectifs',
            self::ARTICLES_OF_ASSOC => 'Statuts de la société',
            self::ID_CARD => 'Carte Nationale d\'Identité (Recto/Verso)',
            self::PASSPORT => 'Passeport',
        };
    }
}
