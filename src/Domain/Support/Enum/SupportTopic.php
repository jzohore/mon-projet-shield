<?php

namespace App\Domain\Support\Enum;

enum SupportTopic: string
{
    case CREATE = 'create';
    case EDIT = 'edit';
    case DELETE = 'delete';
    case DOCUMENTS = 'documents';
    case INVOICE = 'invoice';
    case CREDITS = 'credits';
    case UPGRADE = 'upgrade';
    case BUG = 'bug';
    case QUESTION = 'question';
    case OTHER = 'other';

    /**
     * Retourne le libellé affiché à l'utilisateur lors du choix de l'étape 2.
     */
    public function getTitle(): string
    {
        return match ($this) {
            self::CREATE => 'Créer un dossier',
            self::EDIT => 'Éditer des informations',
            self::DELETE => 'Supprimer / Archiver',
            self::DOCUMENTS => 'Gestion des documents',
            self::INVOICE => 'Où est ma facture ?',
            self::CREDITS => 'Problème de crédits',
            self::UPGRADE => 'Changer de forfait',
            self::BUG => 'Signaler un bug',
            self::QUESTION => 'Poser une question',
            self::OTHER => 'Autre problème',
        };
    }
}
