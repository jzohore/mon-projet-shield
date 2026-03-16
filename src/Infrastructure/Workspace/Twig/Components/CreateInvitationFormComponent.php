<?php

namespace App\Infrastructure\Workspace\Twig\Components;

use App\Application\Workspace\DTO\Request\CreateWorkspaceInvitationRequest;
use App\Application\Workspace\UseCase\CreateWorkspaceInvitationUseCase;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Enum\InvitedRole;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use App\Infrastructure\Shared\Component\LiveFlashTrait;
use App\Infrastructure\Workspace\Form\WorkspaceInvitationType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'CreateInvitationFormComponent',
    template: 'components/Workspace/CreateInvitationFormComponent.html.twig',
)]
class CreateInvitationFormComponent
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use LiveFlashTrait;

    #[LiveProp]
    public string $userSlugId;

    #[LiveProp(writable: true)]
    public string $invitedRole = 'ROLE_WORKSPACE_COLLAB';

    #[LiveProp]
    public bool $showAllInvitations = false;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly LoggerInterface $logger,
        private readonly CreateWorkspaceInvitationUseCase $createWorkspaceInvitationUseCase,
        private readonly WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(WorkspaceInvitationType::class, new CreateWorkspaceInvitationRequest());
    }

    #[LiveAction]
    public function save(): void
    {
        $this->clearLiveFlash();
        $this->submitForm();

        /** @var CreateWorkspaceInvitationRequest $dto */
        $dto = $this->getForm()->getData();

        try {
            $dto->userSlugId = $this->userSlugId;
            $dto->invitedRole = InvitedRole::from($this->invitedRole);
            ($this->createWorkspaceInvitationUseCase)($dto);
            $this->resetForm();

            $this->invitedRole = 'ROLE_WORKSPACE_COLLAB';
            $this->addLiveFlash('success', 'L\'invitation a bien été envoyée.');
        } catch (\DomainException $e) {
            $this->logger->error('Erreur métier lors de la création d\'une invitation', [
                'error' => $e->getMessage(),
            ]);
        }


    }

    #[LiveAction]
    public function showAllInvitations(): void
    {
        $this->showAllInvitations = true;
    }

    /**
     * @return array<int, WorkspaceInvitation>
     */
    public function getInvitations(): array
    {
        $user = $this->userRepository->findBySlug($this->userSlugId);
        Assert::isInstanceOf($user, User::class);
        $workspace = $user->workspace;
        Assert::isInstanceOf($workspace, Workspace::class);
        return $this->workspaceInvitationRepository->findByWorkspace($workspace);
    }
}
