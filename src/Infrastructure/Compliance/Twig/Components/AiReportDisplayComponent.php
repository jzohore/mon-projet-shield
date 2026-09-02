<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Twig\Components;

use App\Application\Compliance\DTO\Response\HolisticMeetingReportDto;
use App\Application\Compliance\UseCase\ComplianceFolder\BuildHolisticMeetingReportUseCase;
use App\Application\Compliance\UseCase\ComplianceFolder\RevokeMeetingReportUseCase;
use App\Application\Compliance\UseCase\ComplianceFolder\ValidateMeetingReportUseCase;
use App\Application\Compliance\UseCase\MeetingRecord\DeleteMeetingAudioUseCase;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Entity\ValidatedMeetingReport;
use App\Domain\Compliance\Enum\MeetingProcessingStatus;
use App\Domain\Compliance\Repository\MeetingRecordRepositoryInterface;
use App\Domain\Compliance\Repository\ValidatedMeetingReportRepositoryInterface;
use App\Domain\Shared\Exception\AbstractDomainException;
use App\Infrastructure\Compliance\Voter\MeetingReportVoter;
use App\Infrastructure\Shared\Component\LiveFlashTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'AiReportDisplayComponent',
    template: 'components/Compliance/AiReportDisplayComponent.html.twig'
)]
final class AiReportDisplayComponent extends AbstractController
{
    use DefaultActionTrait;
    use LiveFlashTrait;

    #[LiveProp(writable: true)]
    public bool $isOpen = true;

    #[LiveProp]
    public string $type;

    #[LiveProp]
    public string $method;

    /**
     * @var array<int, string>
     */
    public ?array $meetingSlugIds = [];

    // 🛡️ NOUVEAU : État éphémère (le front est en train d'enregistrer)
    #[LiveProp]
    public bool $isListening = false;

    #[LiveProp]
    public ComplianceFolder $folder;

    #[LiveProp]
    public bool $isDeleted = false;

    /**
     * Motif de révocation saisi par le CGP (lié au textarea via data-model).
     */
    #[LiveProp(writable: true)]
    public string $revokeReason = '';

    /**
     * Mémo intra-requête du rapport validé en vigueur (non sérialisé).
     */
    private ?ValidatedMeetingReport $inForceReport = null;

    private bool $inForceReportLoaded = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BuildHolisticMeetingReportUseCase $buildHolisticMeetingReportUseCase,
        private readonly MeetingRecordRepositoryInterface $meetingRecordRepository,
        private readonly DeleteMeetingAudioUseCase $deleteMeetingAudioUseCase,
        private readonly ValidateMeetingReportUseCase $validateMeetingReportUseCase,
        private readonly RevokeMeetingReportUseCase $revokeMeetingReportUseCase,
        private readonly ValidatedMeetingReportRepositoryInterface $validatedMeetingReportRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getProcessingStatus(): ?\BackedEnum
    {
        return $this->folder->getMeetingProcessingStatus();
    }

    public function isProcessing(): bool
    {
        $status = $this->getProcessingStatus()?->value;

        return in_array($status, ['finalizing', 'analyzing'], true);
    }

    #[LiveAction]
    public function toggle(): void
    {
        $this->isOpen = !$this->isOpen;
    }

    #[LiveAction]
    public function startListening(): void
    {
        $this->isListening = true;
        $this->isOpen = true;
    }

    #[LiveAction]
    public function startPolling(): void
    {
        // On coupe l'état d'écoute local car on passe la main au serveur
        $this->isListening = false;

        $this->folder->setMeetingProcessingStatus(MeetingProcessingStatus::FINALIZING);
        $this->isOpen = true;
    }

    #[LiveAction]
    public function checkStatus(): void
    {
        $this->entityManager->refresh($this->folder);
    }

    #[LiveAction]
    public function deleteMeetingReport(): void // 🚀 FIX : On ne retourne plus de RedirectResponse !
    {
        $this->meetingSlugIds = $this->getMeetingReport()->slugId;
        try {
            foreach ($this->meetingSlugIds as $meetingSlugId) {
                $meetingReport = $this->meetingRecordRepository->findBySlugId($meetingSlugId);

                Assert::notNull($meetingReport);

                ($this->deleteMeetingAudioUseCase)($meetingReport);
            }

            $this->isDeleted = true;
        } catch (AbstractDomainException $e) {
            $this->logger->warning('Tentative de suppression d\'un meeting report.', [/* ... */]);
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception) {
            $this->logger->error('Crash système.', [/* ... */]);
            $this->addFlash('error', 'Erreur système lors de la suppression.');
        }
    }

    public function getMeetingReport(): HolisticMeetingReportDto
    {
        return ($this->buildHolisticMeetingReportUseCase)($this->folder);
    }

    /**
     * Le rapport figé actuellement en vigueur pour ce dossier, ou null si la
     * synthèse est encore au stade brouillon.
     */
    public function getValidatedReport(): ?ValidatedMeetingReport
    {
        if (!$this->inForceReportLoaded) {
            $this->inForceReport = $this->validatedMeetingReportRepository->findInForceByFolder($this->folder);
            $this->inForceReportLoaded = true;
        }

        return $this->inForceReport;
    }

    public function isValidated(): bool
    {
        return $this->getValidatedReport() instanceof ValidatedMeetingReport;
    }

    #[LiveAction]
    public function validateReport(): void
    {
        $this->denyAccessUnlessGranted(MeetingReportVoter::VALIDATE, $this->folder);

        try {
            ($this->validateMeetingReportUseCase)($this->folder->slugId);
            $this->refreshValidatedReport();
            $this->addFlash('success', 'Rapport d\'entretien validé : il est désormais figé.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Throwable) {
            $this->logger->error('Crash lors de la validation du rapport d\'entretien.', ['folder' => $this->folder->slugId]);
            $this->addFlash('error', 'Erreur système lors de la validation.');
        }
    }

    #[LiveAction]
    public function revokeReport(): void
    {
        $this->denyAccessUnlessGranted(MeetingReportVoter::REVOKE, $this->folder);

        $report = $this->getValidatedReport();

        if (!$report instanceof ValidatedMeetingReport) {
            $this->addFlash('error', 'Aucun rapport validé à révoquer.');

            return;
        }

        try {
            ($this->revokeMeetingReportUseCase)($report->slugId, $this->revokeReason);
            $this->revokeReason = '';
            $this->refreshValidatedReport();
            $this->addFlash('success', 'Rapport d\'entretien révoqué. Vous pouvez en valider une nouvelle version.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Throwable) {
            $this->logger->error('Crash lors de la révocation du rapport d\'entretien.', ['folder' => $this->folder->slugId]);
            $this->addFlash('error', 'Erreur système lors de la révocation.');
        }
    }

    private function refreshValidatedReport(): void
    {
        $this->inForceReport = null;
        $this->inForceReportLoaded = false;
    }
}
