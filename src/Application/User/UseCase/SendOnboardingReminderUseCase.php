<?php

declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\User\Message\SendOnboardingReminderMessage;
use Symfony\Component\Clock\ClockInterface;

use function Symfony\Component\Clock\now;

use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

final readonly class SendOnboardingReminderUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private MessageBusInterface $bus,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ExceptionInterface|\Exception
     */
    public function __invoke(): void
    {
        $thresholdDate = $this->clock->now()->modify('-2 hours');
        $users = $this->userRepository->findUsersNeedingReminder($thresholdDate);

        foreach ($users as $user) {
            Assert::notNull($user->email);
            $message = new SendOnboardingReminderMessage($user->email);
            $this->bus->dispatch($message);

            $user->setOnboardingReminderSentAt(now());
            $this->userRepository->save($user);
        }
    }
}
