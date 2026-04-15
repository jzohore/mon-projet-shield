<?php

namespace App\Application\Screening\UseCase;

use App\Application\Screening\DTO\Request\ScreeningRequest;
use App\Application\Screening\DTO\Response\ScreeningResponse;
use App\Domain\Billing\Enum\CreditAction;
use App\Domain\Billing\Exception\NotEnoughCreditsException;
use App\Domain\Port\OpenSanctionsClientInterface;
use App\Domain\Screening\Entity\ScreeningAudit;
use App\Domain\Screening\Event\ScreeningCompletedEvent;
use App\Domain\Screening\Repository\ScreeningAuditRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Wallet\Enum\TransactionType;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

readonly class PerformScreeningUseCase
{
    public function __construct(
        private ScreeningAuditRepositoryInterface $auditRepository,
        private OpenSanctionsClientInterface $openSanctionsClient,
        private EventDispatcherInterface $eventDispatcher,
        private WorkspaceRepositoryInterface $workspaceRepository,
        private LoggerInterface $logger,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(ScreeningRequest $request): ScreeningResponse
    {
        $user = $this->userRepository->findByEmail($request->userEmail);
        Assert::notNull($user);

        $workspace = $this->workspaceRepository->findOneBySlug($request->workspaceSlugId);
        Assert::notNull($workspace);

        $recentAudit = $this->auditRepository->findRecentIdenticalSearch($workspace, $request->nameToSearch, 24);

        if (null !== $recentAudit) {
            return ScreeningResponse::fromEntity($recentAudit, isCached: true);
        }

        if ($request->chargeCredit && $workspace->balance < 1) {
            throw new NotEnoughCreditsException();
        }

        $apiResult = $this->openSanctionsClient->search($request->nameToSearch, $request->schemaToSearch);
        try {
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
        } catch (\Throwable $e) {
            $this->logger->error('Error during screening: ' . $e->getMessage());
            throw $e;
        }
        $workspaceSlugId = $workspace->slugId;
        Assert::notNull($workspaceSlugId);

        $this->eventDispatcher->dispatch(new ScreeningCompletedEvent(
            auditId: (string) $audit->id,
            workspaceSlugId: $workspaceSlugId,
            userEmail: $request->userEmail,
            query: $request->nameToSearch,
            cost: $request->chargeCredit ? 1 : 0
        ));

        return ScreeningResponse::fromEntity($audit);
    }
}
