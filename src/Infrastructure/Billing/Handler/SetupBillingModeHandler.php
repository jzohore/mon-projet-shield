<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Handler;

use App\Application\Billing\UseCase\Credits\AddCreditsUseCase;
use App\Domain\Billing\Enum\CreditAction;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Enum\WorkspaceType;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Infrastructure\Billing\Message\SetupBillingModeMessage;
use App\Infrastructure\Billing\Service\CreateSubscription;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
readonly class SetupBillingModeHandler
{
    public function __construct(
        private AddCreditsUseCase $addCreditsUseCase,
        private CreateSubscription $createSubscription,
        private WorkspaceRepositoryInterface $workspaceRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(SetupBillingModeMessage $message): void
    {
        $userUuid = Uuid::fromString($message->userId);
        $user = $this->userRepository->getById($userUuid);
        Assert::notNull($user);

        $workspaceUuid = Uuid::fromString($message->workspaceId);
        $workspace = $this->workspaceRepository->getById($workspaceUuid);
        Assert::notNull($workspace->id);

        if (!$user->profile->stripeCustomerId) {
            return; // Sécurité
        }

        $credit = CreditAction::PROMO_CREDIT;
        Assert::notNull($user->id);
        match ($workspace->type) {
            WorkspaceType::INDIVIDUAL => ($this->addCreditsUseCase)($workspace->id, $user->id, $credit->getAmount(), $credit),
            WorkspaceType::FIRM => $this->createSubscription->create($workspace, $user->profile->stripeCustomerId),
        };
    }
}
