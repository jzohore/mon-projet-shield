<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase;

use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Psr\Log\LoggerInterface;

final readonly class VerifyAllWorkspacesOriasUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private VerifyWorkspaceOriasUseCase $verifyWorkspaceOriasUseCase,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Parcourt tous les workspaces et exécute la vérification ORIAS de manière isolée.
     *
     * @return array{success: int, failures: int, errors: array<string, string>}
     */
    public function __invoke(string $performedByEmail = 'cron@kysure.fr'): array
    {
        // Récupération de tous les workspaces enregistrés
        $workspaces = $this->workspaceRepository->findActiveWithSiret();

        $successCount = 0;
        $failureCount = 0;
        $errors = [];

        foreach ($workspaces as $workspace) {
            $slug = $workspace->slugId; // Assumé selon notre convention d'entité

            try {
                ($this->verifyWorkspaceOriasUseCase)($slug, $performedByEmail);
                ++$successCount;
            } catch (\Throwable $e) {
                ++$failureCount;
                $errors[$slug] = $e->getMessage();

                // Log technique de l'erreur isolée sans stopper le batch global
                $this->logger->error(sprintf('Échec du batch ORIAS pour le workspace "%s": %s', $slug, $e->getMessage()), [
                    'workspace_slug' => $slug,
                    'exception' => $e,
                ]);
            }
        }

        return [
            'success' => $successCount,
            'failures' => $failureCount,
            'errors' => $errors,
        ];
    }
}
