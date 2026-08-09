<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifier;

use App\Domain\Notifer\NotifierInterface;
use Symfony\Component\Notifier\Bridge\Slack\SlackOptions;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;

readonly class SlackNotifier implements NotifierInterface
{
    public function __construct(
        private ChatterInterface $chatter,
    ) {
    }

    public function send(string $texteMessage): void
    {
        $message = new ChatMessage($texteMessage);

        $message->transport('workspace_failed_verify');

        $slackOptions = new SlackOptions()
            ->username('Garde-Fou RegTech')
            ->iconEmoji(':rotating_light:'); // Un petit gyrophare 🚨

        $message->options($slackOptions);

        // 5. Envoi
        $this->chatter->send($message);
    }
}
