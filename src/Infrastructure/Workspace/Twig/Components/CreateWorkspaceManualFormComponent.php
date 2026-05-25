<?php

namespace App\Infrastructure\Workspace\Twig\Components;

use App\Application\Workspace\DTO\Request\CreateWorkspaceRequest;
use App\Application\Workspace\UseCase\Onboarding\CreateWorkspaceUseCase;
use App\Infrastructure\Workspace\Form\CreateWorkspaceType;
use DomainException;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'CreateWorkspaceManualFormComponent',
    template: 'components/Workspace/CreateWorkspaceManualFormComponent.html.twig',
)]
class CreateWorkspaceManualFormComponent
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly CreateWorkspaceUseCase $workspaceUseCase,
        private readonly LoggerInterface $logger,
        private readonly UrlGeneratorInterface $router,
        private readonly RequestStack $requestStack,
    ) {}

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(CreateWorkspaceType::class, new CreateWorkspaceRequest(), [
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
            $dto->siret = mt_rand(3, 900) . '0o000000000';

            ($this->workspaceUseCase)($dto);
            return new RedirectResponse($this->router->generate('app_onboarding_plan'));
        } catch (DomainException $e) {
            $this->logger->error('Erreur métier lors de la création du workspace', [
                'workspace_name' => $dto->name,
                'error' => $e->getMessage(),
            ]);
            return null;

        } catch (Exception $e) {
            $this->logger->critical('Crash système lors de la création du workspace', [
                'error' => $e->getMessage(),
            ]);

            /** @var FlashBagAwareSessionInterface $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add(
                type: 'error',
                message: 'Une erreur technique est survenue lors de la création de votre espace. Veuillez réessayer.'
            );

            return new RedirectResponse($this->router->generate('app_onboarding_workspace_manual_config'));
        }
    }
}
