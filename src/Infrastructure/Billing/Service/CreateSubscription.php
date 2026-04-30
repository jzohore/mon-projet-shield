<?php

namespace App\Infrastructure\Billing\Service;

use App\Application\Billing\UseCase\Subscription\CreateSubscriptionUseCase;
use App\Domain\Workspace\Entity\Workspace;
use App\Infrastructure\Service\Payment\Stripe\StripeService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;

readonly class CreateSubscription
{
    public function __construct(
        private LoggerInterface $logger,
        private StripeService $stripeService,
        private CreateSubscriptionUseCase $createSubscriptionUseCase
    ) {}

    public function create(Workspace $workspace, string $stripeCustomerId): void
    {
        try {
            $subId = $this->stripeService->createSubscription($stripeCustomerId);
            ($this->createSubscriptionUseCase)($workspace, $subId);
        } catch (ExceptionInterface $e) {
            // 💡 Ajout du contexte pour le débug
            $this->logger->error('Impossible de créer un abonnement', [
                'workspace_id' => (string) $workspace->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
