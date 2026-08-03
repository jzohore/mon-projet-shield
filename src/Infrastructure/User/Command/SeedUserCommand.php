<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Command;

use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:user:seed',
    description: 'Seed user',
)]
readonly class SeedUserCommand
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(OutputInterface $output): int
    {
        $user = User::create(
            email: 'contact@kysure.fr',
            firstName: 'Junior',
            lastName: 'Zohore',
            isVerified: true,
            roles: ['ROLE_SUPER_ADMIN'],
            onboardingStatus: OnboardingStatus::COMPLETED,
        );

        $this->userRepository->save($user);

        $output->writeln('User created :' . $user->email);

        return Command::SUCCESS;
    }
}
