<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceFolder;

use App\Domain\Compliance\Event\MeetingReportRevokedEvent;
use App\Domain\Compliance\Exception\MeetingReportNotFoundException;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Compliance\Repository\ValidatedMeetingReportRepositoryInterface;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

/**
 * Révoque une version figée de la synthèse d'entretien : elle reste consultable
 * et archivée, mais n'est plus en vigueur. Ouvre la voie à une nouvelle
 * validation (version + 1).
 *
 * L'autorisation « CGP responsable du dossier » relève d'un voter au niveau du
 * contrôleur ; ce use case se limite aux règles métier.
 */
final readonly class RevokeMeetingReportUseCase
{
    public function __construct(
        private ValidatedMeetingReportRepositoryInterface $reportRepository,
        private ComplianceFolderRepositoryInterface $folderRepository,
        private TransactionManagerInterface $transactionManager,
        private CurrentUserProvider $userProvider,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(string $reportSlugId, string $reason): void
    {
        $report = $this->reportRepository->findBySlugId($reportSlugId);

        if (!$report instanceof \App\Domain\Compliance\Entity\ValidatedMeetingReport) {
            throw MeetingReportNotFoundException::withSlugId($reportSlugId);
        }

        $user = $this->userProvider->getUser();
        $reason = trim($reason);

        // Gardes métier (déjà révoqué / motif vide) portées par l'entité :
        // au retour, $reason est garanti non vide et identique à $report->revokeReason.
        $report->revoke($user, $reason);

        $folder = $report->complianceFolder;
        $folder->recordMeetingReportRevoked($report->version, $user->getFullName(), $reason);

        // Révocation + trace d'historique du dossier commitées ensemble, ou pas du tout.
        $this->transactionManager->transactional(function () use ($report, $folder): void {
            $this->reportRepository->save($report);
            $this->folderRepository->save($folder);
        });

        Assert::notNull($report->id);
        $this->eventDispatcher->dispatch(new MeetingReportRevokedEvent(
            reportId: $report->id->toString(),
            reportSlugId: $report->slugId,
            folderSlugId: $folder->slugId,
            version: $report->version,
            reason: $reason,
            revokedByEmail: $user->email,
            revokedByName: $user->getFullName(),
        ));
    }
}
