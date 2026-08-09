<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Twig\Components;

use App\Application\Compliance\DTO\Request\SetIndividualClientRequest;
use App\Application\Compliance\DTO\Response\ComplianceFolderShowResponse;
use App\Application\Compliance\UseCase\ComplianceFolder\ComplianceFolderShowAssembler;
use App\Application\Compliance\UseCase\ComplianceFolder\SetIndividualClientUseCase;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Entity\IndividualFolder;
use App\Domain\Shared\Exception\AbstractDomainException;
use App\Infrastructure\Compliance\Form\SetIndividualClientType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponder;

#[AsLiveComponent(
    name: 'ManualIndividualStepOneComponent',
    template: 'components/Compliance/ManualIndividualStepOneComponent.html.twig',
)]
class ManualIndividualStepOneComponent extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp(writable: false)]
    public ComplianceFolder $complianceFolder;

    #[LiveProp(writable: false)]
    public string $method;

    #[LiveProp(writable: false)]
    public string $type;

    #[LiveProp]
    public bool $isEditing = false;

    public function __construct(
        private readonly SetIndividualClientUseCase $setClientUseCase,
        private readonly LoggerInterface $logger,
        private readonly ComplianceFolderShowAssembler $complianceFolderShowAssembler,
        private readonly LiveResponder $liveResponder,
    ) {
    }

    /**
     * 🪄 S'exécute au chargement du composant.
     * Si le dossier est vide, on force l'ouverture du formulaire !
     */
    public function mount(ComplianceFolder $complianceFolder): void
    {
        $this->complianceFolder = $complianceFolder;

        if ($complianceFolder->isDraftEmpty()) {
            $this->isEditing = true;
        }
    }

    public function complianceDTO(): ComplianceFolderShowResponse
    {
        return $this->complianceFolderShowAssembler->assemble($this->complianceFolder);
    }

    #[LiveAction]
    public function activateEditing(): void
    {
        $this->isEditing = true;
    }

    protected function instantiateForm(): FormInterface
    {
        $request = new SetIndividualClientRequest();
        $request->reference = $this->complianceFolder->reference;

        if ($this->complianceFolder instanceof IndividualFolder) {
            // Attention : Vérifie bien que ce sont les bons noms dans ton entité (contactFirstName vs firstName)
            $request->firstName = $this->complianceFolder->firstName ?? '';
            $request->lastName = $this->complianceFolder->lastName ?? '';
            $request->email = $this->complianceFolder->email ?? '';
        }

        return $this->createForm(SetIndividualClientType::class, $request);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->submitForm();
        /** @var SetIndividualClientRequest $request */
        $request = $this->getForm()->getData();
        try {
            ($this->setClientUseCase)($request);

            $this->addFlash('success', 'Les informations ont bien été enregistrées');

            $this->isEditing = false;
            $this->liveResponder->emit('clientSaved');
        } catch (AbstractDomainException $e) {
            $this->logger->error('Erreur métier (Étape 1)', [
                'slugId' => $this->complianceFolder->slugId,
                'error' => $e->getMessage(),
            ]);
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Erreur technique (Étape 1)', [
                'slugId' => $this->complianceFolder->slugId,
                'error' => $e->getMessage(),
            ]);
            $this->addFlash('error', 'Une erreur technique est survenue lors de l\'enregistrement.');
        }
    }
}
