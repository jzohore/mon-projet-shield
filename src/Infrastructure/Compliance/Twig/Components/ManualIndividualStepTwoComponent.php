<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Twig\Components;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Enum\DocumentType;
use App\Infrastructure\Shared\Component\LiveFlashTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'ManualIndividualStepTwoComponent',
    template: 'components/Compliance/ManualIndividualStepTwoComponent.html.twig',
)]
class ManualIndividualStepTwoComponent
{
    use DefaultActionTrait;
    use LiveFlashTrait;

    #[LiveProp(writable: false)]
    public ComplianceFolder $folder;

    #[LiveProp(writable: true)]
    public ?DocumentType $type = null;

    #[LiveListener('documentAdded')]
    public function onDocumentAdded(): void
    {
        // On ne fait rien de spécial ici. Le simple fait d'attraper l'événement
        // force le composant parent à se recharger.
    }

    #[LiveListener('documentRemoved')]
    public function onDocumentRemoved(): void
    {
        // On ne fait rien de spécial ici. Le simple fait d'attraper l'événement
        // force le composant parent à se recharger.
    }

    #[LiveListener('documentRequested')]
    public function onDocumentRequested(): void
    {
        // On ne fait rien de spécial ici. Le simple fait d'attraper l'événement
        // force le composant parent à se recharger.
    }

    #[LiveListener('documentCancelRequested')]
    public function onDocumentCancelRequested(): void
    {
        // On ne fait rien de spécial ici. Le simple fait d'attraper l'événement
        // force le composant parent à se recharger.
    }
}
