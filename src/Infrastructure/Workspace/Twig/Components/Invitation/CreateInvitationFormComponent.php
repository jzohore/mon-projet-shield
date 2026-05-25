<?php

namespace App\Infrastructure\Workspace\Twig\Components\Invitation;

use App\Application\Workspace\DTO\Request\CreateWorkspaceInvitationRequest;
use App\Application\Workspace\UseCase\Invitation\CreateWorkspaceInvitationUseCase;
use App\Domain\Shared\Exception\AbstractDomainException;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Enum\InvitedRole;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Infrastructure\Shared\Component\LiveFlashTrait;
use App\Infrastructure\Workspace\Form\WorkspaceInvitationType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'CreateInvitationFormComponent',
    template: 'components/Workspace/CreateInvitationFormComponent.html.twig',
)]
class CreateInvitationFormComponent
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use LiveFlashTrait;


    #[LiveProp(writable: true)]
    public string $invitedRole = 'ROLE_WORKSPACE_COLLAB';

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly LoggerInterface $logger,
        private readonly CreateWorkspaceInvitationUseCase $createWorkspaceInvitationUseCase,
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $router,
        private readonly CurrentWorkspaceProvider $currentWorkspaceProvider,
        private readonly WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository,
    ) {}

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(WorkspaceInvitationType::class, new CreateWorkspaceInvitationRequest());
    }

    #[LiveAction]
    public function save(): RedirectResponse
    {
        $this->clearLiveFlash();
        $this->submitForm();

        /** @var CreateWorkspaceInvitationRequest $dto */
        $dto = $this->getForm()->getData();

        try {
            $dto->invitedRole = InvitedRole::from($this->invitedRole);
            ($this->createWorkspaceInvitationUseCase)($dto);
            $this->resetForm();

            $this->invitedRole = 'ROLE_WORKSPACE_COLLAB';

            /** @var FlashBagAwareSessionInterface $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add(
                type: 'success',
                message: 'L\'invitation a bien été envoyée.'
            );
        } catch (AbstractDomainException $e) {
            $this->logger->error('Erreur métier lors de la création d\'une invitation', [
                'email' => $dto->email,
                'error' => $e->getMessage(),
            ]);


            /** @var FlashBagAwareSessionInterface $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add(
                type: 'error',
                message: $e->getMessage(),
            );
        } catch (\Exception $e) {
            $this->logger->critical('Crash système lors de la création d\'une invitation', [
                'email' => $dto->email,
                'error' => $e->getMessage(),
            ]);

            /** @var FlashBagAwareSessionInterface $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add(
                type: 'error',
                message: 'Une erreur technique est survenue. Veuillez réessayer plus tard.'
            );
        }

        return new RedirectResponse($this->router->generate('app_employees_invitation'));

    }

    /**
     * @return array<int, WorkspaceInvitation>
     */
    public function getInvitations(): array
    {
        $workspace = $this->currentWorkspaceProvider->getWorkspace();
        return $this->workspaceInvitationRepository->findByWorkspace($workspace);
    }

    #[LiveListener('revoke_invitation')]
    public function onInvitationRevoked(): void
    {
        // On ne fait rien de spécial ici. Le simple fait d'attraper l'événement
        // force le composant parent à se recharger.
    }
}
