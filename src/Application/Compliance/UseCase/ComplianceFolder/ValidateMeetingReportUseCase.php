<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceFolder;

use App\Domain\Compliance\Entity\ValidatedMeetingReport;
use App\Domain\Compliance\Enum\MeetingProcessingStatus;
use App\Domain\Compliance\Event\MeetingReportValidatedEvent;
use App\Domain\Compliance\Exception\ComplianceFolderNotFoundException;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Compliance\Repository\ValidatedMeetingReportRepositoryInterface;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

/**
 * Fige la synthèse d'entretien consolidée d'un dossier telle que validée par le
 * CGP : à partir de cet instant elle ne bouge plus, quelles que soient les
 * évolutions des enregistrements source ou de la logique de fusion.
 *
 * L'autorisation « CGP responsable du dossier » relève d'un voter au niveau du
 * contrôleur, comme partout ailleurs dans l'application ; ce use case se limite
 * aux règles métier.
 */
final readonly class ValidateMeetingReportUseCase
{
    public function __construct(
        private ComplianceFolderRepositoryInterface $folderRepository,
        private ValidatedMeetingReportRepositoryInterface $reportRepository,
        private BuildHolisticMeetingReportUseCase $buildHolisticMeetingReport,
        private TransactionManagerInterface $transactionManager,
        private CurrentUserProvider $userProvider,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @return string le slugId du rapport validé créé
     */
    public function __invoke(string $folderSlugId): string
    {
        $folder = $this->folderRepository->findOneBySlugId($folderSlugId);

        if (!$folder instanceof \App\Domain\Compliance\Entity\ComplianceFolder) {
            throw ComplianceFolderNotFoundException::withId($folderSlugId);
        }

        if (MeetingProcessingStatus::DONE !== $folder->getMeetingProcessingStatus()) {
            throw new \DomainException("Le rapport IA n'est pas prêt : l'analyse de l'entretien est encore en cours.");
        }

        if ($this->reportRepository->findInForceByFolder($folder) instanceof ValidatedMeetingReport) {
            throw new \DomainException("Un rapport d'entretien est déjà validé pour ce dossier. Révoquez-le avant d'en valider un nouveau.");
        }

        $report = ($this->buildHolisticMeetingReport)($folder);

        if (!$report->isExplorable) {
            throw new \DomainException("L'analyse IA n'a produit aucun contenu exploitable : il n'y a rien à valider.");
        }

        $user = $this->userProvider->getUser();
        $version = $this->reportRepository->findLatestVersionNumber($folder) + 1;

        $validatedReport = ValidatedMeetingReport::validate(
            complianceFolder: $folder,
            validatedBy: $user,
            content: $report->toArray(),
            sourceRecordingSlugs: array_values($report->slugId),
            version: $version,
        );

        $folder->recordMeetingReportValidated($version, $user->getFullName());

        // Rapport figé + trace d'historique du dossier commités ensemble, ou pas du tout.
        $this->transactionManager->transactional(function () use ($validatedReport, $folder): void {
            $this->reportRepository->save($validatedReport);
            $this->folderRepository->save($folder);
        });

        Assert::notNull($validatedReport->id);
        $this->eventDispatcher->dispatch(new MeetingReportValidatedEvent(
            reportId: $validatedReport->id->toString(),
            reportSlugId: $validatedReport->slugId,
            folderSlugId: $folder->slugId,
            version: $version,
            validatedByEmail: $user->email,
            validatedByName: $user->getFullName(),
        ));

        return $validatedReport->slugId;
    }
}
