<?php

namespace App\Infrastructure\Billing\Handler;

use App\Application\Billing\UseCase\Credits\AddCreditsUseCase;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Billing\Enum\CreditAction;
use App\Domain\Workspace\Enum\WorkspaceType;
use App\Infrastructure\Billing\Message\SetupBillingModeMessage;
use App\Infrastructure\Billing\Service\CreateSubscription;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
readonly class SetupBillingModeHandler
{
    public function __construct(
        private AddCreditsUseCase $addCreditsUseCase,
        private CreateSubscription $createSubscription,
        private WorkspaceRepositoryInterface $workspaceRepository,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(SetupBillingModeMessage $message): void
    {
        $workspace = $this->workspaceRepository->getById($message->workspaceId);
        $user = $this->userRepository->getBySlug($message->userId);

        if (!$user->stripeCustomerId) {
            return; // Sécurité
        }

        $credit = CreditAction::PROMO_CREDIT;
        Assert::notNull($workspace->id);
        Assert::notNull($user->id);
        match ($workspace->type) {
            WorkspaceType::INDIVIDUAL => ($this->addCreditsUseCase)($workspace->id, $user->id, $credit->getAmount(), $credit),
            WorkspaceType::FIRM       => $this->createSubscription->create($workspace, $user->stripeCustomerId),
        };
    }
}
