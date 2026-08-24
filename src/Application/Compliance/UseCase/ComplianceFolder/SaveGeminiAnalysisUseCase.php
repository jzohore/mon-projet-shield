<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceFolder;

use App\Domain\Compliance\Entity\MeetingRecording;
use App\Domain\Compliance\Repository\MeetingRecordRepositoryInterface;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

/**
 * Use Case appelé par le Worker Messenger une fois que l'API Gemini a répondu.
 */
final readonly class SaveGeminiAnalysisUseCase
{
    public function __construct(
        private MeetingRecordRepositoryInterface $recordRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param array{
     *     executiveSummary?: string,
     *     riskProfileDetected?: string,
     *     kycUpdates?: array<int, string>,
     *     actionPlan?: array<int, string>
     * } $geminiRawOutput
     */
    public function __invoke(MeetingRecording $recording, array $geminiRawOutput): void
    {
        $recording->attachGeminiOutput($geminiRawOutput);

        $this->recordRepository->save($recording);

        Assert::notNull($recording->id);
        $this->logger->info('Analyse IA attachée avec succès', [
            'recording_id' => $recording->id->toRfc4122(),
        ]);
    }
}
