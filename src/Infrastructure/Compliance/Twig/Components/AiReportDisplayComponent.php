<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Twig\Components;

use App\Application\Compliance\DTO\Response\HolisticMeetingReportDto;
use App\Application\Compliance\UseCase\ComplianceFolder\BuildHolisticMeetingReportUseCase;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Enum\MeetingProcessingStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'AiReportDisplayComponent',
    template: 'components/Compliance/AiReportDisplayComponent.html.twig'
)]
final class AiReportDisplayComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public bool $isOpen = true;

    // 🛡️ NOUVEAU : État éphémère (le front est en train d'enregistrer)
    #[LiveProp]
    public bool $isListening = false;

    #[LiveProp]
    public ComplianceFolder $folder;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BuildHolisticMeetingReportUseCase $buildHolisticMeetingReportUseCase,
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

    // 🚀 NOUVELLE ACTION : Déclenchée au "Play"
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

    public function getMeetingReport(): HolisticMeetingReportDto
    {
        return ($this->buildHolisticMeetingReportUseCase)($this->folder);
    }
}
