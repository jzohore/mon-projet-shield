<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceFolder;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Event\AcceptedRecordingEvent;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class MarkAsAcceptedRecordingUseCase
{
    public function __construct(
        private ComplianceFolderRepositoryInterface $complianceFolderRepository,
        private EventDispatcherInterface $eventDispatcher,
        private CurrentUserProvider $currentUserProvider,
    ) {
    }

    public function __invoke(ComplianceFolder $complianceFolder): void
    {
        $complianceFolder->markAsRecording();
        $this->complianceFolderRepository->save($complianceFolder);

        $user = $this->currentUserProvider->getUser();

        $this->eventDispatcher->dispatch(new AcceptedRecordingEvent(
            folderSlugId: $complianceFolder->slugId,
            userSlugId: $user->slugId,
        ));
    }
}
