<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Twig\Components\Onboarding;

use App\Application\Compliance\DTO\Request\SetIndividualClientRequest;
use App\Application\Compliance\DTO\Response\ComplianceFolderShowResponse;
use App\Application\Compliance\UseCase\ComplianceFolder\ComplianceFolderShowAssembler;
use App\Application\Compliance\UseCase\ComplianceFolder\SetIndividualClientUseCase;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Entity\IndividualFolder;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Infrastructure\Compliance\Form\SetIndividualClientType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponder;

#[AsLiveComponent(
    name: 'FlashStep2FormComponent',
    template: 'components/Compliance/Onboarding/FlashStep2FormComponent.html.twig',
)]
final class FlashStep2FormComponent extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public bool $isVisible = false;

    #[LiveProp(writable: true)]
    public ?ComplianceFolder $complianceFolder = null;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ComplianceFolderRepositoryInterface $repository,
        private readonly SetIndividualClientUseCase $setIndividualUseCase,
        private readonly ComplianceFolderShowAssembler $complianceFolderShowAssembler,
        private readonly LiveResponder $responder,
    ) {
    }

    public function mount(): void
    {
        $this->loadFolderFromSession();
        // S'affiche si le dossier existe ET qu'il est vide
        if ($this->complianceFolder instanceof ComplianceFolder && $this->complianceFolder->isDraftEmpty()) {
            $this->isVisible = true;
        }
    }

    public function getComplianceDTO(): ?ComplianceFolderShowResponse
    {
        if (!$this->complianceFolder instanceof ComplianceFolder) {
            return null;
        }

        return $this->complianceFolderShowAssembler->assemble($this->complianceFolder);
    }

    #[LiveListener('draftCreated')]
    #[LiveListener('backToForm')] // Vient de l'étape 3 (Bouton Modifier)
    public function showForm(): void
    {
        $this->loadFolderFromSession();
        $this->isVisible = true;
    }

    private function loadFolderFromSession(): void
    {
        $draftId = $this->requestStack->getSession()->get('flash_draft_folder_id');
        if (is_string($draftId) || is_int($draftId)) {
            $this->complianceFolder = $this->repository->findById((string) $draftId);
        }
    }

    protected function instantiateForm(): FormInterface
    {
        if (!$this->complianceFolder instanceof ComplianceFolder) {
            return $this->createFormBuilder()->getForm();
        }

        $request = new SetIndividualClientRequest();
        $request->reference = $this->complianceFolder->reference;

        if ($this->complianceFolder instanceof IndividualFolder) {
            $request->firstName = $this->complianceFolder->firstName ?? '';
            $request->lastName = $this->complianceFolder->lastName ?? '';
            $request->email = $this->complianceFolder->email ?? '';

            return $this->createForm(SetIndividualClientType::class, $request);
        }

        return $this->createFormBuilder()->getForm(); // Personne morale à venir
    }

    #[LiveAction]
    public function resetType(): void
    {
        $this->isVisible = false;
        $this->responder->emit('resetToStep1');
    }

    #[LiveAction]
    public function saveDraft(): void
    {
        $this->submitForm();
        $requestData = $this->getForm()->getData();
        if (!$requestData instanceof SetIndividualClientRequest || !$this->complianceFolder instanceof ComplianceFolder) {
            throw new \LogicException('Données invalides.');
        }
        try {
            $requestData->reference = $this->complianceFolder->reference;
            ($this->setIndividualUseCase)($requestData);
            $this->resetForm();
            $this->isVisible = false;
            $this->responder->emit('formSaved'); // Prévient l'étape 3
        } catch (\Exception) {
            $this->addFlash('error', 'Erreur lors de la sauvegarde.');
        }
    }
}
