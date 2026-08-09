<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Command;

use App\Application\Billing\DTO\Request\CreateProductRequest;
use App\Application\Billing\UseCase\Products\CreateProductUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-products',
    description: 'Initialise les produits et abonnements sur Stripe et en base de données',
)]
class InitProductsCommand extends Command
{
    public function __construct(
        private readonly CreateProductUseCase $createProductUseCase,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Format : [Reference, Nom, Crédits, Prix (centimes), Recommandé, Description, Est_Récurrent]
        $packs = [
            [
                'pack_starter',
                'Pack Starter',
                50,
                5000,
                false,
                "Allocation flexible conçue pour la gestion des due diligences ponctuelles. Les crédits n'expirent pas.",
                false,
            ],
            [
                'pack_business',
                'Pack Business',
                200,
                18000,
                true,
                "Standardisez vos processus LCB-FT et sécurisez vos entrées en relation avec un coût d'analyse optimisé.",
                false,
            ],
            [
                'pack_ultra',
                'Pack Ultra',
                1000,
                80000,
                false,
                "Conçu pour l'industrialisation des procédures de conformité.",
                false,
            ],
            [
                'plan_cabinet', // 👈 La ref de l'abonnement
                'Plan Cabinet (Abonnement)',
                0, // Ou crédits illimités selon ta logique
                34900, // 349€
                false,
                'Accès complet au logiciel KYC/KYB avec monitoring automatisé mensuel.',
                true,
            ],
        ];

        foreach ($packs as [$reference, $name, $credits, $price, $isRecommended, $description, $isRecurring]) {
            $request = new CreateProductRequest();
            $request->reference = $reference;
            $request->name = $name;
            $request->credits = $credits;
            $request->priceInCents = $price;
            $request->description = $description;
            $request->isRecommended = $isRecommended;
            $request->isRecurring = $isRecurring;

            $response = ($this->createProductUseCase)($request);

            $type = $isRecurring ? '🔄 Abonnement' : '📦 Pack unique';
            $io->success("{$type} : {$response->name} -> Stripe: {$response->stripePriceId}");
        }

        $io->success('Tous les produits ont été synchronisés avec succès sur Stripe et en BDD.');

        return Command::SUCCESS;
    }
}
