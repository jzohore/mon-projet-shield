<?php

namespace App\Infrastructure\Workspace\Twig\Components;

use App\Application\Workspace\DTO\Request\CreateWorkspaceRequest;
use App\Application\Workspace\UseCase\CreateWorkspaceUseCase;
use App\Infrastructure\Workspace\Form\CreateWorkspaceType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

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

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly CreateWorkspaceUseCase $workspaceUseCase,
        private readonly LoggerInterface $logger,
        private readonly UrlGeneratorInterface $router,
    ) {}

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(CreateWorkspaceType::class, new CreateWorkspaceRequest());
    }

    #[LiveAction]
    public function save(): ?RedirectResponse
    {

        $this->submitForm();

        /** @var CreateWorkspaceRequest $dto */
        $dto = $this->getForm()->getData();

        try {
            $dto->userSlugId = $this->userSlugId;
            ($this->workspaceUseCase)($dto);

        } catch (\DomainException $e) {
            $this->logger->error('Erreur métier lors de la création du workspace', [
                'workspace_name' => $dto->name,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        return new RedirectResponse($this->router->generate('app_onboarding_profile', ['slugId' => $this->userSlugId]));
    }
}
