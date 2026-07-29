<?php

declare(strict_types=1);

namespace App\Infrastructure\ExternalApi\Command;

use App\Application\Workspace\UseCase\VerifyAllWorkspacesOriasUseCase;
use App\Application\Workspace\UseCase\VerifyWorkspaceOriasUseCase;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:orias:verify',
    description: 'Vérifie l\'immatriculation légale sur le registre national ORIAS.',
)]
final readonly class VerifyOriasCommand
{
    public function __construct(
        private VerifyAllWorkspacesOriasUseCase $verifyAllWorkspacesOriasUseCase,
        private VerifyWorkspaceOriasUseCase $verifyWorkspaceOriasUseCase,
    ) {
    }

    public function __invoke(#[Argument('Numéro ORIAS brut (ex: 07001234) OU Slug ID du Workspace.')] ?string $slug, InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('KYSURE - Synchro & Vérification Registre National ORIAS');

        // Cas 1 : Vérification ciblée d'un seul cabinet
        if (is_string($slug) && '' !== $slug) {
            $io->section(sprintf('Vérification unitaire pour le workspace : %s', $slug));
            try {
                ($this->verifyWorkspaceOriasUseCase)($slug, 'cli@kysure.fr');
                $io->success(sprintf('Vérification ORIAS réussie pour le workspace "%s".', $slug));

                return Command::SUCCESS;
            } catch (\Throwable $e) {
                $io->error(sprintf('Échec pour "%s" : %s', $slug, $e->getMessage()));

                return Command::FAILURE;
            }
        }

        // Cas 2 : Vérification de masse (Batch global pour tous les cabinets)
        $io->section('Lancement de la vérification globale de tous les cabinets...');

        $report = ($this->verifyAllWorkspacesOriasUseCase)();

        $io->table(
            ['Statut', 'Nombre'],
            [
                ['<fg=green>Succès</>', $report['success']],
                ['<fg=red>Échecs</>', $report['failures']],
            ]
        );

        if ($report['failures'] > 0) {
            $io->warning('Certains cabinets ont rencontré des erreurs lors de l\'interrogation ORIAS :');
            foreach ($report['errors'] as $workspaceSlug => $errorMsg) {
                $io->text(sprintf(' - <comment>%s</comment> : %s', $workspaceSlug, $errorMsg));
            }

        // On retourne SUCCESS car le batch global est allé au bout,
        // mais les erreurs individuelles ont été loggées et rapportées.
        } else {
            $io->success('Tous les workspaces ont été vérifiés et synchronisés avec succès !');
        }

        return Command::SUCCESS;
    }
}
