<?php

declare(strict_types=1);

namespace App\Infrastructure\Ocr\Command;

use App\Domain\Kyc\Repository\KycDocumentRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:test-ocr',
    description: 'Teste l\'extraction OCR via Mindee sur un fichier local'
)]
class TestOcrCommand extends Command
{
    public function __construct(
        private readonly KycDocumentRepositoryInterface $kycDocumentRepository,
    ) {
        parent::__construct();
    }

    public function __invoke(OutputInterface $output): int
    {
        $this->kycDocumentRepository->findPendingDocuments();

        //        foreach ($document as $doc) {
        //            $this->eventDispatcher->dispatch(new OcrEvent($doc));
        //        }

        return Command::SUCCESS;
    }
}
