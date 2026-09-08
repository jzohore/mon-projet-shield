<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\Invitation;

use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Exception\InvitationAlreadyUsedException;
use App\Domain\Workspace\Exception\NotWorkspaceAdminException;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Infrastructure\Workspace\Message\DispatchInvitationEmailMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

final readonly class ResendWorkspaceInvitationUseCase
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private UrlGeneratorInterface $router,
        private WorkspaceInvitationRepositoryInterface $repository,
        private CurrentUserProvider $currentUserProvider,
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
    ) {
    }

    public function __invoke(WorkspaceInvitation $workspaceInvitation): void
    {
        $currentUser = $this->currentUserProvider->getUser();
        if (!$this->workspaceMemberRepository->isUserAdminOfWorkspace($currentUser, $workspaceInvitation->workspace)) {
            throw NotWorkspaceAdminException::create();
        }

        if (!$workspaceInvitation->isPending()) {
            throw InvitationAlreadyUsedException::create();
        }

        $workspaceInvitation->generateMagicLinkToken();
        $this->repository->save($workspaceInvitation);

        Assert::notNull($workspaceInvitation->id);
        Assert::stringNotEmpty($workspaceInvitation->plainMagicLinkToken);

        $url = $this->router->generate('portal_user_confirm_token', [
            'token' => $workspaceInvitation->plainMagicLinkToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->messageBus->dispatch(new DispatchInvitationEmailMessage(
            $workspaceInvitation->id->toString(),
            $url,
        ));
    }
}
