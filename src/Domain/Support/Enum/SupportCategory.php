<?php

declare(strict_types=1);

namespace App\Domain\Support\Enum;

enum SupportCategory: string
{
    case KYC = 'kyc';
    case KYB = 'kyb';
    case BILLING = 'billing';
    case OTHER = 'other';

    /**
     * Titre principal affiché à l'étape 1.
     */
    public function getTitle(): string
    {
        return match ($this) {
            self::KYC => 'Dossier KYC',
            self::KYB => 'Analyse KYB',
            self::BILLING => 'Facturation',
            self::OTHER => 'Autre demande',
        };
    }

    /**
     * Sous-titre descriptif affiché à l'étape 1.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::KYC => 'Particuliers & Identité',
            self::KYB => 'Entreprises & UBO',
            self::BILLING => 'Crédits & Abonnements',
            self::OTHER => 'Bugs, suggestions...',
        };
    }

    /**
     * Icône associée (utilisée par symfony/ux-icons).
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::KYC => 'lucide:user-check',
            self::KYB => 'lucide:building-2',
            self::BILLING => 'lucide:credit-card',
            self::OTHER => 'lucide:help-circle',
        };
    }

    /**
     * Libellé utilisé par le robot système dans l'auto-réponse.
     * Ex: "Nous avons bien reçu votre demande concernant [votre dossier KYC].".
     */
    public function getContextLabel(): string
    {
        return match ($this) {
            self::KYC => 'votre dossier KYC',
            self::KYB => 'une analyse KYB',
            self::BILLING => 'la facturation',
            self::OTHER => 'une demande générale',
        };
    }

    /**
     * Règle métier : Définit les sous-sujets autorisés pour chaque catégorie.
     * Cette méthode remplace la logique qui polluait le composant Live.
     *
     * @return SupportTopic[]
     */
    public function getAllowedTopics(): array
    {
        return match ($this) {
            self::KYC, self::KYB => [
                SupportTopic::CREATE,
                SupportTopic::EDIT,
                SupportTopic::DELETE,
                SupportTopic::DOCUMENTS,
                SupportTopic::OTHER,
            ],
            self::BILLING => [
                SupportTopic::INVOICE,
                SupportTopic::CREDITS,
                SupportTopic::UPGRADE,
            ],
            self::OTHER => [
                SupportTopic::BUG,
                SupportTopic::QUESTION,
                SupportTopic::OTHER,
            ],
        };
    }
}
