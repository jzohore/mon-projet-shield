<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Handler;

use App\Domain\Compliance\Enum\MeetingProcessingStatus;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Compliance\Service\MeetingAnalyzerInterface;
use App\Domain\Port\DocumentStorageInterface;
use App\Infrastructure\Compliance\Message\AnalyzeCompleteMeetingMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class AnalyzeCompleteMeetingHandler
{
    public function __construct(
        private ComplianceFolderRepositoryInterface $folderRepository,
        private MeetingAnalyzerInterface $analyzer,
        private LoggerInterface $logger,
        private DocumentStorageInterface $storage,
    ) {
    }

    public function __invoke(AnalyzeCompleteMeetingMessage $message): void
    {
        $folder = $this->folderRepository->findOneBySlugId($message->folderSlugId);

        if (!$folder) {
            $this->logger->error('Analyse IA avortée : Dossier introuvable.', ['slug' => $message->folderSlugId]);

            return;
        }

        try {
            $this->logger->info('Génération du lien sécurisé S3 pour l\'audio...');
            $audioTemporaryUrl = $this->storage->getTemporaryUrl($message->audioFilePath);

            $this->logger->info('Lancement de l\'analyse Gemini via flux réseau...');
            $reportDto = $this->analyzer->analyzeCompleteMeeting($folder, $audioTemporaryUrl);

            $folder->setPostMeetingReport([
                'summary' => $reportDto->executiveSummary,
                'riskProfile' => $reportDto->riskProfileDetected,
                'kycUpdates' => $reportDto->kycUpdates,
                'actionPlan' => $reportDto->actionPlan,
                'analyzedAt' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
            ]);

            $folder->setMeetingProcessingStatus(MeetingProcessingStatus::DONE);
            $this->folderRepository->save($folder);
            $this->logger->info('Analyse IA terminée et sauvegardée en base.');

            //            $this->eventDispatcher->dispatch(new MeetingAnalyzedEvent(
            //                $folder->slugId,
            //                $message->audioFilePath,
            //                $message->consumedSeconds,
            //            ));
        } catch (\Throwable $e) {
            $this->logger->critical('CRASH lors de l\'analyse IA : ' . $e->getMessage(), [
                'folder' => $message->folderSlugId,
                'trace' => $e->getTraceAsString(),
            ]);

            // On ne fait PAS de unlink() car le fichier n'est pas sur le disque local.
            // On throw l'exception pour que Messenger passe le message en "failed" et retente plus tard.
            throw $e;
        }
    }
}
