<?php

namespace App\Application\Workspace\DTO\Response;

use App\Domain\Workspace\Entity\WorkspaceInvitation;
use Webmozart\Assert\Assert;

readonly class WorkspaceInvitationInfoResponse
{
    public function __construct(
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $slugId,
        public string $status,
        public string $token,
        public \DateTimeImmutable $tokenExpiresAt,
        public \DateTimeImmutable $createdAt,
        public ?string $role = null,
        public ?string $workspaceName = null,
        public ?string $ownerFullName = null,
        public bool $isTokenValid = false,
    ) {}

    public static function fromEntity(WorkspaceInvitation $invitation): self
    {
        Assert::notNull($invitation->email);
        Assert::notNull($invitation->firstName);
        Assert::notNull($invitation->lastName);
        Assert::notNull($invitation->slugId);
        Assert::notNull($invitation->magicLinkToken);
        Assert::notNull($invitation->magicLinkTokenExpiresAt);

        return new self(
            email: $invitation->email,
            firstName: $invitation->firstName,
            lastName: $invitation->lastName,
            slugId: $invitation->slugId,
            status: $invitation->invitationStatus->getLabel(),
            token: $invitation->magicLinkToken,
            tokenExpiresAt: $invitation->magicLinkTokenExpiresAt,
            createdAt: $invitation->createdAt,
            role: $invitation->invitedRole?->getLabel(),
            workspaceName: $invitation->workspace?->name,
            ownerFullName: $invitation->owner?->getFullName(),
            isTokenValid: $invitation->isMagicLinkTokenValid(),
        );
    }
}
