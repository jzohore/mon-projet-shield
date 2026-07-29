<?php

declare(strict_types=1);

namespace App\Application\Kyc\UseCase;

use App\Application\Kyc\DTO\Request\CreateKycFolderRequest;
use App\Domain\Kyc\Entity\KycFolder;
use App\Domain\Kyc\Enum\KycFolderStatus;
use App\Domain\Kyc\Event\KycFolderCreatedEvent;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class CreateKycFolderUseCase
{
    public function __construct(
        private KycFolderRepositoryInterface $kycFolderRepository,
        private WorkspaceRepositoryInterface $workspaceRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(CreateKycFolderRequest $request): void
    {
        $workspace = $this->workspaceRepository->getBySlug($request->workspaceSlugId);

        $kyc = KycFolder::initiate(
            workspace: $workspace,
            firstName: $request->contactLastName,
            lastName: $request->contactFirstName,
            email: $request->contactEmail,
            status: KycFolderStatus::AWAITING_CLIENT,
        );
        $kyc->generateShareToken();

        $this->kycFolderRepository->save($kyc);
        $this->eventDispatcher->dispatch(new KycFolderCreatedEvent($kyc));
    }
}
