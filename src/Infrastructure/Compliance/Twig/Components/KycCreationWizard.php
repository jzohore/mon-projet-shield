<?php

namespace App\Infrastructure\Compliance\Twig\Components;

use App\Application\Compliance\UseCase\CreateDraftFolderUseCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;

#[AsLiveComponent(
    name: 'KycCreationWizard',
    template: 'components/Compliance/KycCreationWizard.html.twig',
)]
class KycCreationWizard
{
    use DefaultActionTrait;

    // S'il est null, on n'affiche que la première ligne (Le choix de l'entité)
    // S'il est rempli, on affiche la deuxième ligne (Les méthodes)

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CreateDraftFolderUseCase $createDraftFolderUseCase,
    ) {}
    #[LiveProp]
    public ?string $entityType = null;

    #[LiveAction]
    public function selectType(#[LiveArg] string $type): void
    {
        // Quand on clique, on enregistre le choix, ce qui déclenche l'affichage du reste
        $this->entityType = $type;
    }

    #[LiveAction]
    public function selectMethod(#[LiveArg] string $method): RedirectResponse
    {
        if (!$this->entityType) {
            throw new \LogicException('Un type d\'entité doit être sélectionné en premier.');
        }

        $responseDto  = ($this->createDraftFolderUseCase)($this->entityType);
        return new RedirectResponse($this->urlGenerator->generate('app_compliance_method_new', [
            'type' => $this->entityType,
            'method' => $method,
            'slugId' => $responseDto->slugId,
        ]));
    }
}
