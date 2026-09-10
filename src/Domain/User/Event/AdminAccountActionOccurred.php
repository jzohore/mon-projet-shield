<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

use App\Domain\User\Enum\AdminAccountAction;

/**
 * Émis par les use cases de gestion de l'équipe KYSURE après chaque mutation d'un
 * compte du back-office. Ne porte que des scalaires : l'action DELETED survient
 * alors que l'entité Admin n'existe plus.
 */
readonly class AdminAccountActionOccurred
{
    /**
     * @param array<string, scalar|null> $context détails additionnels (rôles avant/après, champs modifiés…)
     */
    public function __construct(
        public AdminAccountAction $action,
        public string $adminSlugId,
        public string $adminEmail,
        public string $adminFirstName,
        public string $adminFullName,
        public string $initiatorEmail,
        public string $initiatorName,
        public array $context = [],
    ) {
    }
}
