<?php

namespace App\Application\Workspace\DTO\Request;

use App\Domain\Workspace\Validator\UniqueWorkspaceName;
use Symfony\Component\Validator\Constraints as Assert;

class CreateWorkspaceRequest
{
    #[Assert\NotBlank(message: 'Le nom de l\'espace est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        // On ajoute les chiffres (0-9), le &, le point (\.) et la virgule (,)
        pattern: '/^[\p{L}0-9\s\-\'\.,&]+$/u',
        message: 'Ce champ contient des caractères non autorisés (les symboles spéciaux comme <, >, = sont interdits).'
    )]
    #[UniqueWorkspaceName]
    public ?string $name = null;

    public ?string $userSlugId = null;
}
