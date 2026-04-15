<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Request\WorkspaceInvitationRequest;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Infrastructure\Workspace\Message\DispatchInvitationEmailMessage;
use App\Infrastructure\Workspace\Persistence\WorkspaceInvitationRepository;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

final readonly class ResendWorkspaceInvitationUseCase
{
    public function __construct(
        private WorkspaceInvitationRepository $workspaceInvitationRepository,
        private MessageBusInterface $messageBus,
        private UrlGeneratorInterface $router,
    ) {}

    public function __invoke(WorkspaceInvitationRequest $request): void
    {
        Assert::notNull($request->slugId);
        $invitation = $this->workspaceInvitationRepository->findBySlugId($request->slugId);
        Assert::isInstanceOf($invitation, WorkspaceInvitation::class);
        $url = $this->router->generate('portal_user_confirm_token', [
            'token' => $invitation->magicLinkToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $message = new DispatchInvitationEmailMessage(
            $invitation->slugId,
            $url,
        );
        $this->messageBus->dispatch($message);
    }
}
