<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Command;

use App\Domain\Database\TransactionManagerInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Enum\Industry;
use App\Domain\Workspace\Enum\InvitedRole;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Webmozart\Assert\Assert;

#[AsCommand(
    name: 'app:workspace:seed',
    description: 'Seed workspace Kysure',
)]
readonly class SeedWorkspaceCommand
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private WorkspaceRepositoryInterface $workspaceRepository,
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = 'Kysure'; // Siret valide à 14 chiffres pour la conformité

        // 1. Contrôle d'idempotence
        $existingWorkspace = $this->workspaceRepository->findOneByName($name);
        if ($existingWorkspace instanceof Workspace) {
            $io->warning(sprintf('Le workspace "%s" existe déjà.', $existingWorkspace->name));

            return Command::SUCCESS;
        }

        // 2. Récupération de l'utilisateur admin
        $user = $this->userRepository->getByEmail('contact@kysure.fr');
        Assert::notNull($user->slugId, 'L\'utilisateur admin doit posséder un slugId valide.');

        // 3. Création du Workspace
        $workspace = Workspace::create(
            name: 'Kysure',
            legalName: 'Kysure SAS',
            address: '4, villa des épinettes',
            etatAdministratif: 'A',
            industry: Industry::OTHER,
            email: 'kysure@contact.fr'
        );

        $workspaceMember = WorkspaceMember::create($workspace, $user, InvitedRole::ROLE_WORKSPACE_ADMIN);
        // 4. On lie l'utilisateur au workspace (Règle métier KYSURE)
        $workspace->addMember($workspaceMember);

        // 5. Persistance
        $this->transactionManager->transactional(function () use ($workspace, $user, $workspaceMember): void {
            $this->workspaceRepository->save($workspace);
            $this->userRepository->save($user);
            $this->workspaceMemberRepository->save($workspaceMember);
        });

        $io->success(sprintf('Workspace "%s" créé avec succès et associé à %s !', $workspace->name, $user->email));

        return Command::SUCCESS;
    }
}
