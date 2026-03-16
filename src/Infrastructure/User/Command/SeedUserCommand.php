<?php

namespace App\Infrastructure\User\Command;

use App\Application\User\DTO\Request\CreateUserRequest;
use App\Application\User\UseCase\CreateUserUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(
    name: 'app:user:seed',
    description: 'Seed user',
)]
readonly class SeedUserCommand
{
    public function __construct(
        private CreateUserUseCase $createUserUseCase,
    ) {}

    public function __invoke(): int
    {
        $request = new CreateUserRequest();
        $request->email = 'test@gmail.com';
        $request->firstName = 'John';
        $request->lastName = 'Doe';
        $request->isAdmin = true;
        $request->isVerified = true;

        $this->createUserUseCase->__invoke($request);

        return Command::SUCCESS;
    }
}
