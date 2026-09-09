<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Listener\Invitation;

use App\Domain\Workspace\Event\WorkspaceInvitationResentEvent;
use App\Infrastructure\Workspace\Message\DispatchInvitationEmailMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

#[AsEventListener]
readonly class DispatchResentInvitationWorkspaceEmail
{
    public function __construct(
        private UrlGeneratorInterface $router,
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(WorkspaceInvitationResentEvent $event): void
    {
        $invitation = $event->workspaceInvitation;

        Assert::notNull($invitation->id);
        Assert::stringNotEmpty($invitation->plainMagicLinkToken, 'Jeton d\'invitation manquant.');

        $url = $this->router->generate('portal_user_confirm_token', [
            'token' => $invitation->plainMagicLinkToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->messageBus->dispatch(new DispatchInvitationEmailMessage(
            invitationId: $invitation->id->toString(),
            url: $url,
        ));
    }
}
