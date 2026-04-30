<?php

namespace App\Infrastructure\Workspace\Twig\Components;

use App\Application\Workspace\DTO\Request\CreateWorkspaceRequest;
use App\Application\Workspace\UseCase\CreateWorkspaceUseCase;
use App\Domain\Workspace\Enum\Industry;
use App\Infrastructure\Service\SiretSearchService;
use App\Infrastructure\Workspace\Form\CreateWorkspaceType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
class CreateWorkspaceFormComponent
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?string $userSlugId = null;

    #[LiveProp(writable: true)]
    public string $searchQuery = '';

    #[LiveProp]
    public ?string $workspaceName = null;

    #[LiveProp]
    public ?string $workspaceSiret = null;

    #[LiveProp]
    public ?string $workspaceAddress = null;

    #[LiveProp]
    public ?string $workspaceIndustry = null;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly CreateWorkspaceUseCase $workspaceUseCase,
        private readonly LoggerInterface $logger,
        private readonly UrlGeneratorInterface $router,
        private readonly SiretSearchService $siretSearchService,
    ) {}

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(CreateWorkspaceType::class, new CreateWorkspaceRequest());
    }

    #[LiveAction]
    public function company(
        #[LiveArg]
        string $name,
        #[LiveArg]
        string $siret,
        #[LiveArg]
        string $address,
        #[LiveArg]
        string $industry,
    ): void {
        $this->formValues['name'] = $name;
        $this->formValues['siret'] = $siret;
        $this->formValues['address'] = $address;
        $this->workspaceIndustry = $industry;

        // On vide la recherche pour fermer la liste proprement
        $this->searchQuery = '';
    }

    #[LiveAction]
    public function resetSelection(): void
    {
        $this->formValues['name'] = null;
        $this->formValues['siret'] = null;
        $this->formValues['address'] = null;
        $this->workspaceIndustry = null;
        $this->searchQuery = '';
    }

    #[LiveAction]
    public function save(): ?RedirectResponse
    {
        $this->submitForm();

        /** @var CreateWorkspaceRequest $dto */
        $dto = $this->getForm()->getData();

        try {
            Assert::notNull($this->userSlugId);
            $dto->userSlugId = $this->userSlugId;
            $dto->legalName = $this->formValues['name'];
            $dto->workspaceIndustry = Industry::fromApeCode($this->workspaceIndustry);

            ($this->workspaceUseCase)($dto);

        } catch (\DomainException $e) {
            $this->logger->error('Erreur métier lors de la création du workspace', [
                'workspace_name' => $dto->name,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        return new RedirectResponse($this->router->generate('app_onboarding_plan'));
    }

    /**
     * @return array<int, array{siret: string, name: string, address: string}>
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getResults(): array
    {
        // On ne cherche rien si le champ est vide ou s'il y a moins de 3 caractères (pour économiser l'API)
        if ($this->workspaceSiret !== null || strlen($this->searchQuery) < 3) {
            return [];
        }

        return $this->siretSearchService->search($this->searchQuery);
    }

}
