<?php

namespace App\Infrastructure\Workspace\Command;

use App\Application\Workspace\DTO\Request\CreateWorkspaceRequest;
use App\Application\Workspace\UseCase\Onboarding\CreateWorkspaceUseCase;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

#[AsCommand(
    name: 'app:workspace:seed',
    description: 'Seed workspace Kysure',
)]
readonly class SeedWorkspaceCommand
{
    public function __construct(
        private CreateWorkspaceUseCase $createWorkspaceUseCase,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(OutputInterface $output): int
    {
        $user = $this->userRepository->getByEmail('contact@kysure.fr');
        Assert::notNull($user->slugId);
        $workspaceRequest = new CreateWorkspaceRequest();
        $workspaceRequest->name = 'Kysure';
        $workspaceRequest->siret = '90900900';
        $workspaceRequest->address = 'Paris';
        $workspaceRequest->legalName = 'Kysure';

        $workspaceResponse = ($this->createWorkspaceUseCase)($workspaceRequest);

        $output->writeln('Workspace created :' . $workspaceResponse->name);
        return Command::SUCCESS;
    }
}
