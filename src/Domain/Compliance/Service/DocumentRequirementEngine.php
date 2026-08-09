<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Service;

use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Entity\IndividualFolder;
use App\Domain\Compliance\Enum\DocumentType;

class DocumentRequirementEngine
{
    public function generateBaseRequirements(ComplianceFolder $folder): void
    {
        // 🙎‍♂️ RÈGLES KYC (Physique)
        if ($folder instanceof IndividualFolder) {
            $folder->requireDocument(DocumentType::ID_CARD, isMandatory: true);
            $folder->requireDocument(DocumentType::PROOF_OF_ADDRESS, isMandatory: true);
        }

        // 🏢 RÈGLES KYB (Personne Morale)
        elseif ($folder instanceof BusinessFolder) {
            $folder->requireDocument(DocumentType::KBIS, isMandatory: true);
            $folder->requireDocument(DocumentType::ARTICLES_OF_ASSOC, isMandatory: true);
            $folder->requireDocument(DocumentType::RBE, isMandatory: true);
        }
    }
}
