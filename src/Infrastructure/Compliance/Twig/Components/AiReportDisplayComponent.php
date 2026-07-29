<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Twig\Components;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Enum\MeetingProcessingStatus;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'AiReportDisplayComponent',
    template: 'components/Compliance/AiReportDisplayComponent.html.twig'
)]
class AiReportDisplayComponent
{
    use DefaultActionTrait;

    public function __construct(
        private readonly ComplianceFolderRepositoryInterface $folderRepository,
    ) {
    }

    #[LiveProp]
    public string $slugId;

    #[LiveProp(writable: true)]
    public bool $isOpen = false;

    public function getFolder(): ?ComplianceFolder
    {
        return $this->folderRepository->findOneBySlugId($this->slugId);
    }

    // 🛡️ Le statut vient directement de la base : plus de risque de désync
    // entre le flag local du component (perdu au refresh) et la réalité du traitement.
    public function getProcessingStatus(): ?MeetingProcessingStatus
    {
        return $this->getFolder()?->getMeetingProcessingStatus();
    }

    public function isProcessing(): bool
    {
        $status = $this->getProcessingStatus();

        return MeetingProcessingStatus::FINALIZING === $status
            || MeetingProcessingStatus::ANALYZING === $status;
    }

    #[LiveAction]
    public function startPolling(): void
    {
        $this->isOpen = true;
    }

    #[LiveAction]
    public function checkStatus(): void
    {
        // Rien à faire ici : le re-render Twig relit getProcessingStatus() à chaque poll.
        // On garde l'action pour déclencher le re-render via le polling Symfony UX.
        if (MeetingProcessingStatus::DONE === $this->getProcessingStatus()) {
            $this->isOpen = true;
        }
    }

    #[LiveAction]
    public function toggle(): void
    {
        $this->isOpen = !$this->isOpen;
    }
}
