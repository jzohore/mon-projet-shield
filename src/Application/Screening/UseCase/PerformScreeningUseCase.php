<?php

declare(strict_types=1);

namespace App\Application\Screening\UseCase;

use App\Application\Screening\DTO\Request\ScreeningRequest;
use App\Application\Screening\DTO\Response\ScreeningResponse;
use App\Domain\Port\OpenSanctionsClientInterface;
use App\Domain\Screening\Entity\ScreeningAudit;
use App\Domain\Screening\Event\ScreeningCompletedEvent;
use App\Domain\Screening\Repository\ScreeningAuditRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class PerformScreeningUseCase
{
    public function __construct(
        private ScreeningAuditRepositoryInterface $auditRepository,
        private OpenSanctionsClientInterface $openSanctionsClient,
        private EventDispatcherInterface $eventDispatcher,
        private CurrentUserProvider $currentUserProvider,
        private CurrentWorkspaceProvider $currentWorkspaceProvider,
    ) {
    }

    public function __invoke(ScreeningRequest $request): ScreeningResponse
    {
        $user = $this->currentUserProvider->getUser();
        $workspace = $this->currentWorkspaceProvider->getWorkspace();

        $recentAudit = $this->auditRepository->findRecentIdenticalSearch($workspace, $request->nameToSearch, 24);

        if ($recentAudit instanceof ScreeningAudit) {
            return ScreeningResponse::fromEntity($recentAudit, isCached: true);
        }

        $apiResult = $this->openSanctionsClient->search($request->nameToSearch, $request->schemaToSearch);

        $audit = ScreeningAudit::create(
            workspace: $workspace,
            ower: $user,
            query: $request->nameToSearch,
            results: $apiResult['alerts'],
            totalMatches: $apiResult['total_matches'],
        );

        $this->auditRepository->save($audit);

        $this->eventDispatcher->dispatch(new ScreeningCompletedEvent(
            workspace: $workspace,
            user: $user,
            screeningAudit: $audit,
        ));

        return ScreeningResponse::fromEntity($audit);
    }
}
