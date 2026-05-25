<?php

namespace App\Domain\Compliance\Entity;

use App\Domain\Common\Attribute\Encrypted;
use App\Domain\Workspace\Entity\Workspace;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
class IndividualFolder extends ComplianceFolder
{
    #[ORM\Column(length: 100, nullable: true)]
    #[Encrypted]
    public private(set) ?string $firstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Encrypted]
    public private(set) ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Encrypted]
    public private(set) ?string $email = null;

    /**
     * CONSTRUCTEUR PROTÉGÉ
     */
    protected function __construct(
        Workspace $workspace,
        string $reference,
    ) {
        parent::__construct($workspace, $reference);
    }

    /**
     * STATIC FACTORY METHOD pour la création de brouillon
     */
    public static function createDraft(Workspace $workspace, string $reference): self
    {
        $folder = new self($workspace, $reference);

        $folder->saveHistory('Dossier initié (Brouillon)');

        return $folder;
    }

    /**
     * Exemple de méthode métier : Changement d'email avec traçabilité
     */
    public function updateEmail(string $newEmail): void
    {
        if ($this->email === $newEmail) {
            return;
        }

        $oldEmail = $this->email;
        $this->email = $newEmail;
        $this->saveHistory('Email mis à jour', "De {$oldEmail} vers {$newEmail}");
    }
}
