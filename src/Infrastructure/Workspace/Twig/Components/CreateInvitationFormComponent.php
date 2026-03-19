<?php

namespace App\Infrastructure\Workspace\Twig\Components;

use App\Application\Workspace\DTO\Request\CreateWorkspaceInvitationRequest;
use App\Application\Workspace\DTO\Request\WorkspaceInvitationRequest;
use App\Application\Workspace\UseCase\CreateWorkspaceInvitationUseCase;
use App\Application\Workspace\UseCase\ResendWorkspaceInvitationUseCase;
use App\Application\Workspace\UseCase\RevokeWorkspaceInvitationUseCase;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Enum\InvitedRole;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Infrastructure\Shared\Component\LiveFlashTrait;
use App\Infrastructure\Workspace\Form\WorkspaceInvitationType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
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

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly LoggerInterface $logger,
        private readonly CreateWorkspaceInvitationUseCase $createWorkspaceInvitationUseCase,
        private readonly RevokeWorkspaceInvitationUseCase $revokeWorkspaceInvitationUseCase,
        private readonly ResendWorkspaceInvitationUseCase $resendWorkspaceInvitationUseCase,
        private readonly WorkspaceInvitationRepositoryInterface $workspaceInvitationRepository,
        private readonly WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
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
    #[IsGranted('ROLE_WORKSPACE_ADMIN')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function revokeInvitation(#[LiveArg] string $id): void
    {
        $this->clearLiveFlash();

        try {

            $dto = new WorkspaceInvitationRequest();
            $dto->slugId = $id;
            ($this->revokeWorkspaceInvitationUseCase)($dto);

            $this->addLiveFlash('success', 'L\'invitation a été annulée.');

        } catch (\DomainException $e) {
            $this->logger->warning('Tentative de révocation échouée', [
                'invitationId' => $id,
                'error' => $e->getMessage(),
            ]);

            $this->addLiveFlash('error', $e->getMessage());
        }
    }

    #[LiveAction]
    public function resendInvitation(#[LiveArg] string $id): void
    {
        $this->clearLiveFlash();

        try {

            $dto = new WorkspaceInvitationRequest();
            $dto->slugId = $id;
            ($this->resendWorkspaceInvitationUseCase)($dto);

            $this->addLiveFlash('success', 'L\'invitation a été envoyé de nouveau.');

        } catch (\DomainException $e) {
            $this->logger->warning('Tentative de d\'envoie échouée', [
                'invitationId' => $id,
                'error' => $e->getMessage(),
            ]);

            $this->addLiveFlash('error', $e->getMessage());
        }
    }

    /**
     * @return array<int, WorkspaceInvitation>
     */
    public function getInvitations(): array
    {
        $user = $this->userRepository->findBySlug($this->userSlugId);
        Assert::isInstanceOf($user, User::class);
        $workspaceMember = $this->workspaceMemberRepository->findOneByUser($user);
        Assert::notNull($workspaceMember);
        $workspace = $workspaceMember->workspace;
        Assert::notNull($workspace);
        return $this->workspaceInvitationRepository->findByWorkspace($workspace);
    }
}
