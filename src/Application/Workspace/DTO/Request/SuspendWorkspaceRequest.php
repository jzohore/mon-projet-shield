<?php

declare(strict_types=1);

namespace App\Application\Workspace\DTO\Request;

use App\Domain\User\Entity\Admin;
use App\Domain\Workspace\Entity\Workspace;
use Symfony\Component\Validator\Constraints as Assert;

final class SuspendWorkspaceRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le raison de suspension du cabinet est obligatoire.')]
        #[Assert\Length(max: 3000)]
        public ?string $suspensionReason = null,
        public string $deletedByEmail = '',
        public string $deletedByFullName = '',
    ) {
    }

    /**
     * Factory method pour pré-remplir le formulaire.
     */
    public static function fromEntity(Workspace $workspace, Admin $admin): self
    {
        $dto = new self();
        $dto->suspensionReason = $workspace->suspensionReason;

        $dto->deletedByEmail = $admin->getUserIdentifier();
        $dto->deletedByFullName = trim($admin->getFullName());

        return $dto;
    }
}
