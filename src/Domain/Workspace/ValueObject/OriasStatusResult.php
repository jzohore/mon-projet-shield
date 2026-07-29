<?php

declare(strict_types=1);

namespace App\Domain\Workspace\ValueObject;

use Webmozart\Assert\Assert;

final readonly class OriasStatusResult
{
    /**
     * @param array<int, string> $categories
     * @param array<int, string> $associations
     */
    public function __construct(
        public string $oriasNumber,
        public bool $isValid,
        public ?string $registrationStatus,
        public ?string $legalName,
        public array $categories = [],
        public array $associations = [],
        public ?string $errorMessage = null,
    ) {
        Assert::allString($this->categories, 'Chaque catégorie doit être une chaîne de caractères.');
        Assert::allString($this->associations, 'Chaque association doit être une chaîne de caractères.');
    }

    public static function invalid(string $oriasNumber, string $reason): self
    {
        return new self(
            oriasNumber: $oriasNumber,
            isValid: false,
            registrationStatus: null,
            legalName: null,
            categories: [],
            associations: [],
            errorMessage: $reason
        );
    }
}
