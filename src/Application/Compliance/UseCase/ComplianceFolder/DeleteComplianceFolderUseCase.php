<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceFolder;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Event\DeleteComplianceEvent;
use App\Domain\Compliance\Exception\CannotDeleteActiveFolderException;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class DeleteComplianceFolderUseCase
{
    public function __construct(
        private ComplianceFolderRepositoryInterface $complianceFolderRepository,
        private EventDispatcherInterface $eventDispatcher,
        private CurrentUserProvider $userProvider,
    ) {
    }

    public function __invoke(ComplianceFolder $complianceFolder): void
    {
        if (!$complianceFolder->isDraft()) {
            throw CannotDeleteActiveFolderException::forFolder($complianceFolder->reference);
        }

        $user = $this->userProvider->getUser();

        $complianceFolder->markAsDeleted();
        $this->complianceFolderRepository->save($complianceFolder);

        $this->eventDispatcher->dispatch(new DeleteComplianceEvent(
            folderSlugId: $complianceFolder->slugId,
            userSlugId: $user->slugId,
        ));
    }
}
