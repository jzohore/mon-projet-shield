<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase\Invitation;

use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
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
    ) {
    }

    public function __invoke(WorkspaceInvitation $workspaceInvitation): void
    {
        $workspaceInvitation->clearMagicLinkToken();
        $workspaceInvitation->generateMagicLinkToken();

        $this->repository->save($workspaceInvitation);
        Assert::notNull($workspaceInvitation->id);
        $url = $this->router->generate('portal_user_confirm_token', [
            'token' => $workspaceInvitation->magicLinkToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $message = new DispatchInvitationEmailMessage(
            $workspaceInvitation->id->toString(),
            $url,
        );
        $this->messageBus->dispatch($message);
    }
}
