<?php

declare(strict_types=1);

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
use Symfony\Component\RateLimiter\RateLimiterFactory;
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
    use ComponentWithFormTrait;
    use DefaultActionTrait;
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
        private readonly RateLimiterFactory $workspaceInvitationLimiter,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(WorkspaceInvitationType::class, new CreateWorkspaceInvitationRequest());
    }

    #[LiveAction]
    public function save(): ?RedirectResponse
    {
        $this->clearLiveFlash();
        $this->submitForm();

        // On laisse le LiveComponent ré-afficher les erreurs de champ sans recharger.
        if (!$this->getForm()->isValid()) {
            return null;
        }

        $ip = $this->requestStack->getCurrentRequest()?->getClientIp() ?? 'unknown';
        if (!$this->workspaceInvitationLimiter->create($ip)->consume()->isAccepted()) {
            $this->flash('error', 'Trop d\'invitations envoyées récemment. Merci de réessayer plus tard.');

            return new RedirectResponse($this->router->generate('app_employees_invitation'));
        }

        /** @var CreateWorkspaceInvitationRequest $dto */
        $dto = $this->getForm()->getData();

        try {
            $dto->invitedRole = InvitedRole::tryFrom($this->invitedRole) ?? InvitedRole::ROLE_WORKSPACE_COLLAB;
            ($this->createWorkspaceInvitationUseCase)($dto);
            $this->resetForm();

            $this->invitedRole = 'ROLE_WORKSPACE_COLLAB';
            $this->flash('success', 'L\'invitation a bien été envoyée.');
        } catch (AbstractDomainException $e) {
            $this->logger->error('Erreur métier lors de la création d\'une invitation', [
                'email' => $dto->email,
                'error' => $e->getMessage(),
            ]);
            $this->flash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical('Crash système lors de la création d\'une invitation', [
                'email' => $dto->email,
                'error' => $e->getMessage(),
            ]);
            $this->flash('error', 'Une erreur technique est survenue. Veuillez réessayer plus tard.');
        }

        return new RedirectResponse($this->router->generate('app_employees_invitation'));
    }

    private function flash(string $type, string $message): void
    {
        $session = $this->requestStack->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add($type, $message);
        }
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
