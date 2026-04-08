<?php

namespace App\Infrastructure\Billing\Command;

use App\Application\Billing\DTO\Request\CreateProductRequest;
use App\Application\Billing\UseCase\CreateProductUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:products:init',
    description: 'Initialise les 3 packs de crédits Stripe'
)]
readonly class CreateInitialProductsCommand
{
    public function __construct(
        private CreateProductUseCase $createProductUseCase,
    ) {}

    public function __invoke(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $packs = [
            [
                'Pack Starter',
                50,
                5000,
                'price_1TJbFaJA36TONOO9yo4OE30f',
                false,
                "Allocation flexible conçue pour la gestion des due diligences ponctuelles. Permet l'exécution immédiate d'audits KYC/KYB et la génération de rapports d'adéquation réglementaires. Les crédits acquis n'expirent pas, assurant une couverture de conformité à votre rythme.",
            ],
            [
                'Pack Business',
                200,
                18000,
                'price_1TJbGDJA36TONOO9mjsRP4Hl',
                true,
                "Dimensionné pour l'onboarding réglementaire continu des cabinets et études. Standardisez vos processus LCB-FT et sécurisez vos entrées en relation avec un coût d'analyse optimisé. L'outil central pour fluidifier l'acquisition client tout en garantissant une piste d'audit fiable.",
            ],
            [
                'Pack Ultra',
                1000,
                80000,
                'price_1TJbGvJA36TONOO92Ill4CWt',
                false,
                "Conçu pour l'industrialisation des procédures de conformité. Cette allocation maximise votre ROI opérationnel sur les forts volumes d'audit (structures complexes, portefeuilles MIF 2 étendus). Le standard pour les directions de la conformité exigeantes.",
            ],
        ];
        foreach ($packs as [$name, $credits, $price, $stripeId, $isRecommended, $description]) {
            $request = new CreateProductRequest();
            $request->name = $name;
            $request->credits = $credits;
            $request->priceInCents = $price;
            $request->stripePriceId = $stripeId;
            $request->description = $description;
            $request->isRecommended = $isRecommended;

            // Appel du UseCase de manière Invokable !
            $response = ($this->createProductUseCase)($request);

            $io->success("Initialisé : {$response->name} -> Stripe: {$response->stripePriceId}");
        }

        $io->success('Tous les packs ont été créés avec succès en base de données.');

        return Command::SUCCESS;
    }
}
