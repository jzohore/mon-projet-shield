<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Command;

use App\Application\Billing\UseCase\Pricing\SyncStripePricingUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:billing:sync-pricing',
    description: 'Provisionne les offres KYSURE (abonnements au siège + packs de minutes) sur Stripe et en base.',
)]
final class SyncStripePricingCommand extends Command
{
    public function __construct(
        private readonly SyncStripePricingUseCase $syncStripePricing,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $report = ($this->syncStripePricing)();

        $io->table(
            ['Offre', 'Action', 'Stripe price id'],
            array_map(
                static fn (array $row): array => [$row['key'], $row['action'], $row['priceId'] ?? '—'],
                $report,
            ),
        );

        $io->success('Offres synchronisées avec Stripe et enregistrées en base.');

        return Command::SUCCESS;
    }
}
