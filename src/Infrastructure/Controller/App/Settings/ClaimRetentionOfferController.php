<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\App\Settings;

use App\Application\Billing\UseCase\Subscription\ClaimRetentionOfferUseCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[IsGranted('ROLE_USER')]
#[Route(path: '/app/settings/subscription/retention', name: 'app_settings_subscription_retention', methods: ['POST'])]
#[IsCsrfTokenValid('subscription-retention')]
final class ClaimRetentionOfferController extends AbstractController
{
    public function __construct(
        private readonly ClaimRetentionOfferUseCase $claimRetentionOffer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(): RedirectResponse
    {
        try {
            ($this->claimRetentionOffer)();
            $this->addFlash('success', 'Offre appliquée : -30 % sur vos 3 prochaines factures. Merci de votre confiance !');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error('Échec de l\'application de l\'offre de fidélité : ' . $e->getMessage());
            $this->addFlash('error', 'L\'offre n\'a pas pu être appliquée. Réessayez dans un instant.');
        }

        return $this->redirectToRoute('app_settings_subscription');
    }
}
