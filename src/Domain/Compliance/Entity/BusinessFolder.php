<?php

namespace App\Domain\Compliance\Entity; // Ajuste selon ton namespace exact

use App\Domain\Kyc\Entity\Stakeholder;
use App\Domain\Workspace\Entity\Workspace;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection; // ✅ Import corrigé
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
class BusinessFolder extends ComplianceFolder
{
    #[ORM\Column(length: 255, nullable: true)]
    public private(set) ?string $companyName = null; // ✅ Encapsulation respectée

    #[ORM\Column(length: 14, unique: true, nullable: true)]
    public private(set) ?string $siret = null;

    #[ORM\Column(length: 255, nullable: true)]
    public private(set) ?string $legalCategory = null;

    /** @var Collection<int, Stakeholder> */
    #[ORM\OneToMany(targetEntity: Stakeholder::class, mappedBy: 'folder', cascade: ['persist', 'remove'])]
    public private(set) Collection $stakeholders;

    /**
     * CONSTRUCTEUR PROTÉGÉ
     * On appelle le parent pour initialiser le statut, l'historique et la date de création.
     */
    protected function __construct(Workspace $workspace, string $reference)
    {
        parent::__construct($workspace, $reference);
        $this->stakeholders = new ArrayCollection(); // ✅ Obligatoire pour Doctrine
    }

    public static function createDraft(Workspace $workspace, string $reference): self
    {
        $folder = new self($workspace, $reference);

        $folder->saveHistory('Dossier initié (Brouillon)');

        return $folder;
    }

    /**
     * Mise à jour de la catégorie juridique (ex: SAS, SARL)
     */
    public function updateLegalCategory(string $category): void
    {
        $this->legalCategory = $category;
        $this->saveHistory('Mise à jour de la forme juridique', $category);
    }

    /**
     * Ajouter un bénéficiaire effectif ou un dirigeant (UBO)
     */
    public function addStakeholder(Stakeholder $stakeholder): void
    {
        if (!$this->stakeholders->contains($stakeholder)) {
            $this->stakeholders->add($stakeholder);
            // On suppose que tu mettras une méthode pour assigner le dossier dans Stakeholder
            // $stakeholder->assignToFolder($this);
            $this->saveHistory('Partie prenante ajoutée au dossier');
        }
    }
}
