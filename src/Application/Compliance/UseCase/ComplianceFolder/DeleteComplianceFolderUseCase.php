<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceFolder;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Event\DeleteComplianceEvent;
use App\Domain\Compliance\Exception\CannotDeleteActiveFolderException;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class DeleteComplianceFolderUseCase
{
    public function __construct(
        private ComplianceFolderRepositoryInterface $complianceFolderRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(ComplianceFolder $complianceFolder): void
    {
        if (!$complianceFolder->isDraft()) {
            CannotDeleteActiveFolderException::forFolder($complianceFolder->reference);
        }

        $this->complianceFolderRepository->remove($complianceFolder);
        $this->eventDispatcher->dispatch(new DeleteComplianceEvent($complianceFolder));
    }
}
