<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Twig\Components;

use App\Application\Workspace\DTO\Request\CreateWorkspaceRequest;
use App\Application\Workspace\UseCase\Onboarding\CreateWorkspaceUseCase;
use App\Domain\Workspace\Enum\Industry;
use App\Infrastructure\Service\SiretSearchService;
use App\Infrastructure\Shared\Component\LiveFlashTrait;
use App\Infrastructure\Workspace\Form\CreateWorkspaceType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'CreateWorkspaceFormComponent',
    template: 'components/Workspace/CreateWorkspaceFormComponent.html.twig',
)]
class CreateWorkspaceFormComponent extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;
    use LiveFlashTrait;

    #[LiveProp(writable: true)]
    public string $searchQuery = '';

    #[LiveProp]
    public ?string $workspaceSiret = null;

    #[LiveProp]
    public ?string $workspaceIndustry = null;

    #[LiveProp]
    public ?string $workspaceSiren = null;

    #[LiveProp]
    public ?string $workspaceEtatAdministratif = null;

    #[LiveProp]
    public ?string $message = null;

    #[LiveProp]
    public bool $showMessage = false;

    public function __construct(
        private readonly CreateWorkspaceUseCase $workspaceUseCase,
        private readonly LoggerInterface $logger,
        private readonly SiretSearchService $siretSearchService,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(CreateWorkspaceType::class, new CreateWorkspaceRequest());
    }

    #[LiveAction]
    public function company(
        #[LiveArg]
        string $name,
        #[LiveArg]
        string $siret,
        #[LiveArg]
        string $siren,
        #[LiveArg]
        string $address,
        #[LiveArg]
        string $etatAdministratif,
        #[LiveArg]
        string $industry,
    ): void {
        $verify = $this->siretSearchService->verifyStatus($siret, $this->searchQuery);

        if (false === $verify->isActive) {
            $this->showMessage = true;
            $this->message = $verify->message;
            $this->searchQuery = '';

            return;
        }
        $this->formValues['name'] = $name;
        $this->formValues['siret'] = $siret;
        $this->formValues['siren'] = $siren;
        $this->formValues['address'] = $address;
        $this->workspaceEtatAdministratif = $etatAdministratif;
        $this->workspaceSiren = $siren;
        $this->workspaceIndustry = $industry;

        // On vide la recherche pour fermer la liste proprement
        $this->searchQuery = '';
    }

    #[LiveAction]
    public function resetSelection(): void
    {
        $this->formValues['name'] = null;
        $this->formValues['siret'] = null;
        $this->formValues['siren'] = null;
        $this->formValues['address'] = null;
        $this->workspaceEtatAdministratif = null;
        $this->workspaceSiren = null;
        $this->workspaceIndustry = null;
        $this->searchQuery = '';
    }

    #[LiveAction]
    public function save(): ?Response
    {
        $this->submitForm();

        /** @var CreateWorkspaceRequest $dto */
        $dto = $this->getForm()->getData();

        try {
            Assert::notNull($this->workspaceSiren, 'Veuillez sélectionner une entreprise valide.');
            Assert::notNull($this->workspaceEtatAdministratif, 'Statut administratif manquant.');

            // Hydratation manuelle
            $dto->legalName = $this->formValues['name'] ?? null;
            $dto->etatAdministratif = $this->workspaceEtatAdministratif;
            $dto->workspaceIndustry = Industry::fromApeCode($this->workspaceIndustry);

            ($this->workspaceUseCase)($dto);

            return $this->redirectToRoute('app_onboarding_plan');
        } catch (\DomainException $e) {
            $this->logger->error('Erreur métier lors de la création du workspace', [
                'workspace_name' => $dto->name,
                'error' => $e->getMessage(),
            ]);

            $this->addFlash(
                type: 'error',
                message: 'Erreur lors de la création du workspace'
            );

            return $this->redirectToRoute('app_onboarding_workspace');
        } catch (\Exception $e) {
            $this->logger->critical('Crash système lors de la création du workspace', [
                'error' => $e->getMessage(),
            ]);

            $this->addFlash(
                type: 'error',
                message: 'Une erreur technique est survenue lors de la création de votre espace. Veuillez réessayer.'
            );

            return $this->redirectToRoute('app_onboarding_workspace');
        }
    }

    /**
     * @return array<int, array{siret: string, name: string, address: string}>
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getResults(): array
    {
        // On ne cherche rien si le champ est vide ou s'il y a moins de 3 caractères (pour économiser l'API)
        if (null !== $this->workspaceSiret || null !== $this->workspaceSiren || strlen($this->searchQuery) < 3) {
            return [];
        }

        return $this->siretSearchService->search($this->searchQuery);
    }
}
