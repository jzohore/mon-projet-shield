<?php

declare(strict_types=1);

namespace App\Application\Screening\UseCase;

use App\Application\Screening\DTO\Request\ScreeningRequest;
use App\Application\Screening\DTO\Response\ScreeningResponse;
use App\Domain\Billing\Enum\CreditAction;
use App\Domain\Billing\Exception\NotEnoughCreditsException;
use App\Domain\Port\OpenSanctionsClientInterface;
use App\Domain\Screening\Entity\ScreeningAudit;
use App\Domain\Screening\Event\ScreeningCompletedEvent;
use App\Domain\Screening\Repository\ScreeningAuditRepositoryInterface;
use App\Domain\Wallet\Enum\TransactionType;
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

        if ($request->chargeCredit && $workspace->balance < 1) {
            throw new NotEnoughCreditsException();
        }

        $apiResult = $this->openSanctionsClient->search($request->nameToSearch, $request->schemaToSearch);
        if ($request->chargeCredit) {
            $workspace->debit(
                action: CreditAction::SCREENING_SEARCH,
                type: TransactionType::SCREENING_SEARCH->value,
            );
        }

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
            cost: $request->chargeCredit ? 1 : 0,
        ));

        return ScreeningResponse::fromEntity($audit);
    }
}
