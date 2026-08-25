<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceFolder;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Event\ArchiveComplianceEvent;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class ArchiveFolderUseCase
{
    public function __construct(
        private ComplianceFolderRepositoryInterface $complianceFolderRepository,
        private CurrentUserProvider $currentUserProvider,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(ComplianceFolder $folder): void
    {
        $user = $this->currentUserProvider->getUser();

        $folder->markAsArchive($user->email);
        $this->complianceFolderRepository->save($folder);

        $this->eventDispatcher->dispatch(new ArchiveComplianceEvent(
            folderSlugId: $folder->slugId,
            userSlugId: $user->slugId,
        ));
    }
}
