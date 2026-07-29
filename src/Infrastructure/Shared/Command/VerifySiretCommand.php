<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Command;

use App\Domain\Workspace\Event\WorkspaceSuspendedEvent;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Infrastructure\Service\SiretSearchService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

#[AsCommand(
    name: 'app:workspace:verify-siret',
    description: 'Vérifie l\'état des SIRET des Workspaces via l\'API Gouvernementale'
)]
readonly class VerifySiretCommand
{
    public function __construct(
        public SiretSearchService $siretSearchService,
        public WorkspaceRepositoryInterface $workspaceRepository,
        public WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        public EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(OutputInterface $output): int
    {
        $workspaces = $this->workspaceRepository->findActiveWithSiret();

        foreach ($workspaces as $workspace) {
            $siren = $workspace->siren;
            $name = $workspace->name;

            try {
                // 2. Appel à l'API (ex: Recherche Entreprise Gouv)

                $sirenResult = $this->siretSearchService->verifyStatus($siren, $name);

                if (false === $sirenResult->isActive) {
                    $workspace->suspend($sirenResult->message, $sirenResult->etatAdministratif);
                    Assert::notNull($workspace->id);

                    $workspaceMember = $this->workspaceMemberRepository->findOneByWorkspace($workspace->id);
                    Assert::notNull($workspaceMember);

                    $user = $workspaceMember->user;
                    Assert::notNull($user);

                    $this->workspaceRepository->save($workspace);
                    $output->writeln($sirenResult->message);

                    $this->eventDispatcher->dispatch(new WorkspaceSuspendedEvent($workspace, $user));
                }
            } catch (\Exception $e) {
                $output->writeln('Erreur API pour le SIRET : ' . $workspace->siren . ' : ' . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
