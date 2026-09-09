<?php

declare(strict_types=1);

namespace App\Application\Workspace\DTO\Response;

use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Enum\InvitedRole;

readonly class WorkspaceInvitationInfoResponse
{
    public function __construct(
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $slugId,
        public string $status,
        public \DateTimeImmutable $createdAt,
        public bool $isTokenValid = false,
        public ?string $token = null,
        public ?\DateTimeImmutable $tokenExpiresAt = null,
        public ?string $role = null,
        public bool $isAdmin = false,
        public ?string $workspaceName = null,
        public ?string $ownerFullName = null,
    ) {
    }

    public static function fromEntity(WorkspaceInvitation $invitation): self
    {
        return new self(
            email: $invitation->email,
            firstName: $invitation->firstName,
            lastName: $invitation->lastName,
            slugId: $invitation->slugId,
            status: $invitation->invitationStatus->getLabel(),
            createdAt: $invitation->createdAt,
            isTokenValid: $invitation->isMagicLinkTokenValid(),
            token: $invitation->magicLinkToken,
            tokenExpiresAt: $invitation->magicLinkTokenExpiresAt,
            role: $invitation->invitedRole->getLabel(),
            isAdmin: InvitedRole::ROLE_WORKSPACE_ADMIN === $invitation->invitedRole,
            workspaceName: $invitation->workspace->name,
            ownerFullName: $invitation->owner->getFullName(),
        );
    }
}
