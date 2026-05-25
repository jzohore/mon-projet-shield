<?php

namespace App\Domain\Compliance\Factory;

use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Entity\IndividualFolder;
use App\Domain\Compliance\Enum\FolderType;
use App\Domain\Workspace\Entity\Workspace;

class ComplianceFolderFactory
{
    public function createDraft(FolderType $type, Workspace $workspace): ComplianceFolder
    {
        // On pourrait injecter ici un service pour générer la référence proprement !
        $reference = 'KYC-' . strtoupper(substr(uniqid(), -6));

        return match ($type) {
            FolderType::INDIVIDUAL => IndividualFolder::createDraft($workspace, $reference),
            FolderType::BUSINESS   => BusinessFolder::createDraft($workspace, $reference),
        };
    }
}
