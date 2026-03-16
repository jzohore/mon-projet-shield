<?php

namespace App\Application\Workspace\DTO\Request;

use App\Domain\Workspace\Enum\InvitedRole;
use App\Domain\Workspace\Validator\UniqueWorkspaceInvitationEmail;
use Symfony\Component\Validator\Constraints as Assert;

class CreateWorkspaceInvitationRequest
{
    #[Assert\NotBlank(message: 'L\'adresse email est obligatoire.')]
    #[Assert\Email(
        message: 'Le format de l\'adresse email est invalide.',
        mode: 'html5'
    )]
    #[Assert\Length(
        max: 180,
        maxMessage: 'L\'email ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[UniqueWorkspaceInvitationEmail]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Veuillez sélectionner le rôle.')]
    public InvitedRole $invitedRole = InvitedRole::ROLE_WORKSPACE_COLLAB;

    public ?string $userSlugId = null;
}
