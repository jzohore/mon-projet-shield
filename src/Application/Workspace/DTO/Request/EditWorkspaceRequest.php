<?php

declare(strict_types=1);

namespace App\Application\Workspace\DTO\Request;

use App\Domain\User\Entity\Admin;
use App\Domain\Workspace\Entity\Workspace;
use Symfony\Component\Validator\Constraints as Assert;

final class EditWorkspaceRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom du cabinet est obligatoire.')]
        #[Assert\Length(max: 100)]
        public string $name = '',
        public ?string $siret = '',
        public ?string $updatedByEmail = '',
        public ?string $updatedByFullName = '',
    ) {
    }

    /**
     * Factory method pour pré-remplir le formulaire.
     */
    public static function fromEntity(Workspace $workspace, Admin $admin): self
    {
        $dto = new self();
        $dto->name = $workspace->name;
        $dto->siret = $workspace->siret ?? '';

        $dto->updatedByEmail = $admin->getUserIdentifier();
        $dto->updatedByFullName = trim($admin->getFullName());

        return $dto;
    }
}
