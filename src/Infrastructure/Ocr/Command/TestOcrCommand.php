<?php

namespace App\Infrastructure\Ocr\Command;

use App\Domain\Kyc\Enum\DocumentType;
use App\Domain\Port\OcrProviderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-ocr',
    description: 'Teste l\'extraction OCR via Mindee sur un fichier local'
)]
class TestOcrCommand extends Command
{
    public function __construct(
        private readonly OcrProviderInterface $ocrProvider
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('filePath', InputArgument::REQUIRED, 'Chemin relatif du fichier (ex: test.jpg)')
            ->addArgument('type', InputArgument::REQUIRED, 'Type de document (id_card ou kbis)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $filePath = $input->getArgument('filePath');
        $typeString = $input->getArgument('type');

        if (!file_exists($filePath)) {
            $io->error("Le fichier $filePath est introuvable.");
            return Command::FAILURE;
        }

        $type = DocumentType::tryFrom($typeString);
        if (!$type) {
            $io->error("Type invalide. Utilisez 'id_card' ou 'kbis'.");
            return Command::FAILURE;
        }

        $io->note("Envoi du document à l'API OCR (Type: {$type->value})...");
        $startTime = microtime(true);

        try {
            $data = $this->ocrProvider->extractData($type, $filePath);

            $duration = round(microtime(true) - $startTime, 2);
            $io->success("Extraction réussie en {$duration} secondes !");

            $io->section('Données extraites :');
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $io->writeln($json !== false ? $json : 'Impossible d\'encoder les données en JSON.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error("Erreur OCR : " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
