<?php

namespace App\Infrastructure\User\Command;

use App\Application\User\UseCase\SendOnboardingReminderUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(
    name: 'app:user:send-reminder-mail',
    description: 'Send reminder mail to users',
)]
readonly class SendReminderMailCommand
{
    public function __construct(
        private SendOnboardingReminderUseCase $sendOnboardingReminderUseCase,
    ) {}
    public function __invoke(): int
    {
        ($this->sendOnboardingReminderUseCase)();
        return Command::SUCCESS;
    }
}
