<?php

declare(strict_types=1);

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
     * CONSTRUCTEUR PROTÉGÉ.
     */
    protected function __construct(
        Workspace $workspace,
        string $reference,
        string $email,
        string $method,
    ) {
        parent::__construct($workspace, $reference, $email, $method);
    }

    /**
     * STATIC FACTORY METHOD pour la création de brouillon.
     */
    public static function createDraft(Workspace $workspace, string $reference, string $email, string $method): self
    {
        return new self($workspace, $reference, $email, $method);
    }

    /**
     * Exemple de méthode métier : Changement d'email avec traçabilité.
     */
    public function updateEmail(string $newEmail): void
    {
        if ($this->email === $newEmail) {
            return;
        }

        $oldEmail = $this->email;
        $this->email = $newEmail;
        $this->saveHistory('Email mis à jour', "De $oldEmail vers $newEmail");
    }

    public function setClientInfo(string $firstName, string $lastName, string $email): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->saveHistory('Les informations clients ont été mis à jour');
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function isDraftEmpty(): bool
    {
        return in_array($this->firstName, [null, '', '0'], true);
    }
}
