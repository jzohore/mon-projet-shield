<?php

namespace App\Infrastructure\Workspace\Listener\Invitation;

use App\Domain\Workspace\Event\WorkspaceInvitationCreatedEvent;
use App\Infrastructure\Workspace\Message\DispatchInvitationEmailMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class DispatchInvitationWorkspaceEmail
{
    public function __construct(
        private UrlGeneratorInterface $router,
        private MessageBusInterface $messageBus,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(WorkspaceInvitationCreatedEvent $event): void
    {
        $invitation = $event->workspaceInvitation;

        Assert::notNull($invitation->id);

        $url = $this->router->generate('portal_user_confirm_token', [
            'token' => $invitation->magicLinkToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $message = new DispatchInvitationEmailMessage(
            invitationId: $invitation->id->toString(),
            url: $url,
        );
        $this->messageBus->dispatch($message);
    }
}
