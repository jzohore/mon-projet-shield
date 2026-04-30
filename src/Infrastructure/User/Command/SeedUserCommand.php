<?php

namespace App\Infrastructure\User\Command;

use App\Application\User\DTO\Request\CreateUserRequest;
use App\Application\User\UseCase\CreateUserUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

#[AsCommand(
    name: 'app:user:seed',
    description: 'Seed user',
)]
readonly class SeedUserCommand
{
    public function __construct(
        private CreateUserUseCase $createUserUseCase,
    ) {}

    public function __invoke(OutputInterface $output): int
    {
        $request = new CreateUserRequest();
        $request->email = 'contact@kysure.fr';
        $request->firstName = 'Junior';
        $request->lastName = 'Zohore';
        $request->isAdmin = true;
        $request->isVerified = true;

        $user = ($this->createUserUseCase)($request);
        Assert::notNull($user);

        $output->writeln('User created :' . $user->email);
        return Command::SUCCESS;
    }
}
