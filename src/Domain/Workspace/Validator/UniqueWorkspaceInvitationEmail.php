<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class UniqueWorkspaceInvitationEmail extends Constraint
{
    public string $message = 'L\'e-mail "{{ value }}" existe déjà.';
    public ?string $ignoreSlugId = null;

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
