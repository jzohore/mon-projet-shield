<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Handler;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Service\Payment\Stripe\StripeService;
use App\Infrastructure\User\Message\CreateStripeCustomerMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateStripeCustomerHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private StripeService $stripeService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateStripeCustomerMessage $message): void
    {
        $user = $this->userRepository->getById($message->userId);

        // 🛡️ Idempotence : si le client Stripe existe déjà (message rejoué, double
        // dispatch, reprise après incident), on ne recrée rien.
        if (null !== $user->profile->stripeCustomerId) {
            $this->logger->info('Client Stripe déjà présent, création ignorée.', [
                'user_id' => $user->slugId,
                'stripe_customer_id' => $user->profile->stripeCustomerId,
            ]);

            return;
        }

        $this->stripeService->createStripeCustomer($user);
    }
}
