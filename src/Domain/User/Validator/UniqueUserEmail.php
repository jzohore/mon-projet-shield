<?php

namespace App\Domain\User\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class UniqueUserEmail extends Constraint
{
    public string $message = 'L\'e-mail "{{ value }}" existe déjà.';
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
