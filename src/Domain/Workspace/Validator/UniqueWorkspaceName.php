<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class UniqueWorkspaceName extends Constraint
{
    public string $message = 'L\'espace de travail "{{ value }}" existe déjà. Veuillez choisir un autre nom.';
    public ?string $ignoreSlugId = null;

    /**
     * @param array<string, mixed> $options  // Ajout du type pour PHPStan
     * @param string[]|null $groups
     */
    public function __construct(
        ?string $ignoreSlugId = null,
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
        array $options = []
    ) {
        parent::__construct($options, $groups, $payload);

        $this->ignoreSlugId = $ignoreSlugId ?? $this->ignoreSlugId;
        $this->message = $message ?? $this->message;
    }
}
