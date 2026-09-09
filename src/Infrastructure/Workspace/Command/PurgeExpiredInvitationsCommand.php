<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Command;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use Psr\Log\LoggerInterface;

use function Symfony\Component\Clock\now;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Minimisation RGPD (à planifier via cron). Une invitation collaborateur jamais
 * acceptée et périmée depuis plus de {--days} jours voit ses nom / prénom /
 * e-mail effacés ; la ligne passe en EXPIRED et sert de simple marqueur.
 * Le journal d'audit du cabinet conserve la trace de l'opération.
 */
#[AsCommand(
    name: 'app:invitations:purge',
    description: 'Anonymise les invitations collaborateur périmées et jamais acceptées.',
)]
final readonly class PurgeExpiredInvitationsCommand
{
    public function __construct(
        private WorkspaceInvitationRepositoryInterface $invitationRepository,
        private AuditLogRepositoryInterface $auditLogRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Ancienneté minimale de péremption, en jours.')]
        int $days = 90,
        #[Option(description: 'Nombre maximum d\'invitations traitées sur cette exécution.')]
        int $limit = 1000,
        #[Option(description: 'Affiche ce qui serait purgé sans rien modifier.')]
        bool $dryRun = false,
    ): int {
        $cutoff = now()->modify(sprintf('-%d days', max(1, $days)));
        $invitations = $this->invitationRepository->findExpiredPending($cutoff, $limit);

        if ([] === $invitations) {
            $io->success(sprintf('Aucune invitation à purger (seuil : %s).', $cutoff->format('d/m/Y')));

            return Command::SUCCESS;
        }

        /** @var array<string, array{workspace: Workspace, count: int}> $byWorkspace */
        $byWorkspace = [];

        foreach ($invitations as $invitation) {
            $io->writeln(
                sprintf('  - %s (cabinet : %s)', $invitation->email, $invitation->workspace->name ?? '—'),
                SymfonyStyle::VERBOSITY_VERBOSE,
            );

            if ($dryRun) {
                continue;
            }

            $invitation->anonymizeAsExpired();
            $this->invitationRepository->save($invitation);

            $key = $invitation->workspace->slugId;
            $byWorkspace[$key] ??= ['workspace' => $invitation->workspace, 'count' => 0];
            ++$byWorkspace[$key]['count'];
        }

        $total = count($invitations);

        if ($dryRun) {
            $io->note(sprintf('%d invitation(s) seraient anonymisées (dry-run).', $total));

            return Command::SUCCESS;
        }

        foreach ($byWorkspace as $entry) {
            $this->auditLogRepository->save(AuditLog::initiate(
                eventName: AuditEventType::WORKSPACE_INVITATION_EXPIRED,
                payload: [
                    'workspace_name' => $entry['workspace']->name,
                    'purged_count' => $entry['count'],
                    'cutoff' => $cutoff->format(\DateTimeInterface::ATOM),
                ],
                workspace: $entry['workspace'],
            ));
        }

        $this->logger->info('Purge RGPD des invitations collaborateur périmées.', [
            'purged' => $total,
            'workspaces' => count($byWorkspace),
            'cutoff' => $cutoff->format(\DateTimeInterface::ATOM),
        ]);

        $io->success(sprintf('%d invitation(s) anonymisée(s) sur %d cabinet(s).', $total, count($byWorkspace)));

        return Command::SUCCESS;
    }
}
