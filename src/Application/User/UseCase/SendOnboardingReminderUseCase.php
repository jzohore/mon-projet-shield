<?php

namespace App\Application\User\UseCase;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\User\Message\SendOnboardingReminderMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

final readonly class SendOnboardingReminderUseCase
{
    public function __construct(
        public UserRepositoryInterface $userRepository,
        public MessageBusInterface $bus,
    ) {}

    public function __invoke(): void
    {
        $users = $this->userRepository->findOnboardingUsers();
        Assert::isArray($users);
        foreach ($users as $user) {
            Assert::isInstanceOf($user, User::class);
            $date = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            $target = $user->createdAt;
            $interval = $date->diff($target)->h;
            if ($interval > 2) {
                $message = new SendOnboardingReminderMessage($user->email);
                $this->bus->dispatch($message);
            }
        }
    }
}
