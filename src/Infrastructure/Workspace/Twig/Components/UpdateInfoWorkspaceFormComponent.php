<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Twig\Components;

use App\Application\Workspace\DTO\Request\UpdateWorkspaceRequest;
use App\Application\Workspace\UseCase\UpdateInfoWorkspaceUseCase;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Infrastructure\Workspace\Form\UpdateWorkspaceType;
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
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'UpdateInfoWorkspaceFormComponent',
    template: 'components/Workspace/UpdateInfoWorkspaceFormComponent.html.twig',
)]
class UpdateInfoWorkspaceFormComponent
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly UpdateInfoWorkspaceUseCase $updateInfoWorkspaceUseCase,
        private readonly LoggerInterface $logger,
        private readonly UrlGeneratorInterface $router,
        private readonly RequestStack $requestStack,
        private readonly CurrentWorkspaceProvider $currentWorkspaceProvider,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        $workspace = $this->currentWorkspaceProvider->getWorkspace();

        $dto = new UpdateWorkspaceRequest();

        Assert::notNull($workspace->industry);
        Assert::notNull($workspace->address);

        $dto->slugId = $workspace->slugId;
        $dto->workspaceIndustry = $workspace->industry;
        $dto->name = $workspace->name;
        $dto->siren = $workspace->siren;
        $dto->siret = $workspace->siret;
        $dto->address = $workspace->address;

        return $this->formFactory->create(UpdateWorkspaceType::class, $dto);
    }

    #[LiveAction]
    public function save(): ?RedirectResponse
    {
        $this->submitForm();

        /** @var UpdateWorkspaceRequest $dto */
        $dto = $this->getForm()->getData();

        try {
            ($this->updateInfoWorkspaceUseCase)($dto);

            /** @var FlashBagAwareSessionInterface $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add(
                type: 'success',
                message: 'Vos informations ont bien été modifié.'
            );

            return new RedirectResponse($this->router->generate('app_settings_organization'));
        } catch (\DomainException $e) {
            $this->logger->error('Erreur métier lors de l\'édition du workspace', [
                'workspace_name' => $dto->name,
                'error' => $e->getMessage(),
            ]);

            return null;
        } catch (\Exception $e) {
            $this->logger->critical('Crash système lors de l\'édition du workspace', [
                'error' => $e->getMessage(),
            ]);

            /** @var FlashBagAwareSessionInterface $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add(
                type: 'error',
                message: 'Une erreur technique est survenue lors de l\'édition de votre espace. Veuillez réessayer.'
            );

            return new RedirectResponse($this->router->generate('app_settings_organization'));
        }
    }
}
