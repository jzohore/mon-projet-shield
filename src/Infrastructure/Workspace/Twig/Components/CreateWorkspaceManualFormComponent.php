<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Twig\Components;

use App\Application\Workspace\DTO\Request\CreateWorkspaceRequest;
use App\Application\Workspace\UseCase\Onboarding\CreateWorkspaceUseCase;
use App\Infrastructure\Workspace\Form\CreateWorkspaceType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'CreateWorkspaceManualFormComponent',
    template: 'components/Workspace/CreateWorkspaceManualFormComponent.html.twig',
)]
class CreateWorkspaceManualFormComponent extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    public function __construct(
        private readonly CreateWorkspaceUseCase $workspaceUseCase,
        private readonly LoggerInterface $logger,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(CreateWorkspaceType::class, new CreateWorkspaceRequest(), [
            'include_siret' => false,
        ]);
    }

    #[LiveAction]
    public function save(): ?RedirectResponse
    {
        $this->submitForm();

        /** @var CreateWorkspaceRequest $dto */
        $dto = $this->getForm()->getData();

        try {
            $dto->legalName = $this->formValues['name'] ?? null;

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

            return null;
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
}
