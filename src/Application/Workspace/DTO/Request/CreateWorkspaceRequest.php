<?php

declare(strict_types=1);

namespace App\Application\Workspace\DTO\Request;

use App\Domain\Workspace\Enum\Industry;
use App\Domain\Workspace\Validator\UniqueWorkspaceName;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateWorkspaceRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
        #[Assert\Length(min: 2, max: 50, minMessage: 'Au moins {{ limit }} caractères.', maxMessage: 'Maximum {{ limit }} caractères.')]
        #[Assert\Regex(pattern: '/^[\p{L}0-9\s\-\'\.,&]+$/u', message: 'Caractères spéciaux interdits.')]
        #[UniqueWorkspaceName]
        public string $name = '',

        #[Assert\Regex(pattern: '/^\d{14}$/', message: 'Le SIRET doit contenir exactement 14 chiffres.')]
        public string $siret = '',

        #[Assert\NotBlank(message: 'L\'adresse de la structure est obligatoire.')]
        #[Assert\Length(max: 255)]
        public string $address = '',

        // 🛡️ NOUVEAU : On blinde les champs hydratés post-formulaire
        public string $etatAdministratif = '',

        #[Assert\Regex(pattern: '/^\d{9}$/', message: 'Le SIREN doit contenir 9 chiffres.')]
        public string $siren = '',

        public string $legalName = '',
        public Industry $workspaceIndustry = Industry::OTHER,
    ) {
    }
}
