<?php

declare(strict_types=1);

namespace App\Domain\Workspace\ValueObject;

use App\Domain\Workspace\Enum\OriasCheckOutcome;
use Webmozart\Assert\Assert;

final readonly class OriasStatusResult
{
    /**
     * @param string       $oriasNumber           identifiant interrogé sur le registre (SIREN)
     * @param ?string      $registeredOriasNumber n° d'immatriculation ORIAS lu sur la fiche, si présent
     * @param list<string> $categories
     * @param list<string> $associations
     */
    private function __construct(
        public string $oriasNumber,
        public OriasCheckOutcome $outcome,
        public ?string $registrationStatus,
        public ?string $legalName,
        public array $categories,
        public array $associations,
        public ?string $errorMessage,
        public ?string $registeredOriasNumber = null,
    ) {
        Assert::allString($this->categories, 'Chaque catégorie doit être une chaîne de caractères.');
        Assert::allString($this->associations, 'Chaque association doit être une chaîne de caractères.');
    }

    /**
     * L'intermédiaire est-il inscrit et actif sur le registre ORIAS ?
     */
    public function isValid(): bool
    {
        return OriasCheckOutcome::VALID === $this->outcome;
    }

    /**
     * Le résultat est-il définitif (par opposition à « registre injoignable, à réessayer ») ?
     */
    public function isConclusive(): bool
    {
        return $this->outcome->isConclusive();
    }

    /**
     * @param list<string> $categories
     * @param list<string> $associations
     */
    public static function valid(
        string $oriasNumber,
        ?string $registrationStatus,
        ?string $legalName,
        array $categories = [],
        array $associations = [],
        ?string $registeredOriasNumber = null,
    ): self {
        return new self(
            oriasNumber: $oriasNumber,
            outcome: OriasCheckOutcome::VALID,
            registrationStatus: $registrationStatus,
            legalName: $legalName,
            categories: $categories,
            associations: $associations,
            errorMessage: null,
            registeredOriasNumber: $registeredOriasNumber,
        );
    }

    /**
     * Réponse définitive : ce numéro n'est pas (ou plus) inscrit à l'ORIAS,
     * ou n'a pas le format d'un numéro d'immatriculation.
     */
    public static function notRegistered(string $oriasNumber, string $reason): self
    {
        return new self(
            oriasNumber: $oriasNumber,
            outcome: OriasCheckOutcome::NOT_REGISTERED,
            registrationStatus: null,
            legalName: null,
            categories: [],
            associations: [],
            errorMessage: $reason,
        );
    }

    /**
     * Le registre n'a pas pu être consulté : ne pas conclure, réessayer plus tard.
     */
    public static function unavailable(string $oriasNumber, string $reason): self
    {
        return new self(
            oriasNumber: $oriasNumber,
            outcome: OriasCheckOutcome::UNAVAILABLE,
            registrationStatus: null,
            legalName: null,
            categories: [],
            associations: [],
            errorMessage: $reason,
        );
    }

    /**
     * @deprecated utiliser {@see self::notRegistered()} ; conservé pour la rétro-compatibilité
     */
    public static function invalid(string $oriasNumber, string $reason): self
    {
        return self::notRegistered($oriasNumber, $reason);
    }
}
