<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Command;

use App\Domain\Compliance\Entity\DerAcknowledgement;
use App\Domain\Compliance\Repository\DerAcknowledgementRepositoryInterface;
use Psr\Log\LoggerInterface;

use function Symfony\Component\Clock\now;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Minimisation RGPD (à planifier via cron) : efface l'IP et le user-agent des
 * accusés de réception du DER au-delà de leur durée de conservation probatoire
 * ({@see DerAcknowledgement::TECHNICAL_DATA_RETENTION_MONTHS} mois). L'accusé
 * lui-même reste intact.
 */
#[AsCommand(
    name: 'app:rgpd:purge-der-technical-data',
    description: 'Efface l\'IP / le user-agent des accusés de réception DER périmés (données probatoires).',
)]
final readonly class PurgeDerTechnicalDataCommand
{
    public function __construct(
        private DerAcknowledgementRepositoryInterface $acknowledgementRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Affiche ce qui serait purgé sans rien modifier.')]
        bool $dryRun = false,
        #[Option(description: 'Nombre maximum d\'accusés traités sur cette exécution.')]
        int $limit = 1000,
    ): int {
        $threshold = now()->modify(sprintf('-%d months', DerAcknowledgement::TECHNICAL_DATA_RETENTION_MONTHS));

        $acknowledgements = $this->acknowledgementRepository->findPurgeableTechnicalData($threshold, max(1, $limit));

        if ([] === $acknowledgements) {
            $io->success(sprintf('Aucune donnée technique à purger (seuil : %s).', $threshold->format('d/m/Y')));

            return Command::SUCCESS;
        }

        foreach ($acknowledgements as $acknowledgement) {
            if ($dryRun) {
                continue;
            }

            $acknowledgement->purgeTechnicalData();
            $this->acknowledgementRepository->save($acknowledgement);
        }

        $count = count($acknowledgements);

        if (!$dryRun) {
            $this->logger->info('Purge RGPD des données techniques d\'accusés de réception DER.', [
                'purged' => $count,
                'threshold' => $threshold->format(\DateTimeInterface::ATOM),
            ]);
        }

        $io->success(sprintf(
            '%s%d accusé(s) : IP / user-agent %s (acquittés avant le %s).',
            $dryRun ? '[DRY-RUN] ' : '',
            $count,
            $dryRun ? 'à purger' : 'purgés',
            $threshold->format('d/m/Y'),
        ));

        return Command::SUCCESS;
    }
}
