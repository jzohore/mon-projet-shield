<?php

declare(strict_types=1);

namespace App\Application\Workspace\DTO\Response;

use App\Domain\Workspace\Entity\WorkspaceMember;

readonly class WorkspaceMemberDetailsResponse
{
    public function __construct(
        public string $userSlugId,
        public string $fullName,
        public string $email,
        public string $role,
        public string $joinedAt,
    ) {
    }

    // 🪄 Le DTO extrait les infos de la relation Member -> User
    public static function fromEntity(WorkspaceMember $member): self
    {
        $user = $member->user;

        return new self(
            userSlugId: $user->slugId,
            fullName: $user->firstName . ' ' . $user->lastName,
            email: $user->email,
            role: $member->role->getLabel(), // Le rôle appartient au Member, pas au User !
            joinedAt: $member->joinedAt->format('d/m/Y'),
        );
    }
}
