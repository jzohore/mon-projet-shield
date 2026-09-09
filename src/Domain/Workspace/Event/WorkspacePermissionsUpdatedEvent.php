<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Event;

use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Émis quand un administrateur modifie les droits délégués aux collaborateurs.
 * Tracé : « qui peut faire quoi dans ce cabinet » doit être reconstituable et daté.
 */
class WorkspacePermissionsUpdatedEvent extends Event
{
    public function __construct(
        public readonly Workspace $workspace,
        public readonly User $updatedBy,
        /** @var array<string, scalar> instantané des réglages après modification */
        public readonly array $snapshot,
    ) {
    }
}
