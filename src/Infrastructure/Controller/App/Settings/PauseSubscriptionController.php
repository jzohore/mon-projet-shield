<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\App\Settings;

use App\Application\Billing\UseCase\Subscription\PauseSubscriptionUseCase;
use App\Application\Billing\UseCase\Subscription\ResumeSubscriptionUseCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[IsGranted('ROLE_USER')]
final class PauseSubscriptionController extends AbstractController
{
    public function __construct(
        private readonly PauseSubscriptionUseCase $pauseSubscription,
        private readonly ResumeSubscriptionUseCase $resumeSubscription,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(path: '/app/settings/subscription/pause', name: 'app_settings_subscription_pause', methods: ['POST'])]
    #[IsCsrfTokenValid('subscription-pause')]
    public function pause(): RedirectResponse
    {
        try {
            ($this->pauseSubscription)();
            $this->addFlash('success', 'Votre abonnement est suspendu. La facturation est interrompue.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error('Échec de suspension de l\'abonnement : ' . $e->getMessage());
            $this->addFlash('error', 'La suspension a échoué. Réessayez dans un instant.');
        }

        return $this->redirectToRoute('app_settings_subscription');
    }

    #[Route(path: '/app/settings/subscription/resume', name: 'app_settings_subscription_resume', methods: ['POST'])]
    #[IsCsrfTokenValid('subscription-resume')]
    public function resume(): RedirectResponse
    {
        try {
            ($this->resumeSubscription)();
            $this->addFlash('success', 'Votre abonnement a repris. La facturation reprendra au prochain cycle.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error('Échec de reprise de l\'abonnement : ' . $e->getMessage());
            $this->addFlash('error', 'La reprise a échoué. Réessayez dans un instant.');
        }

        return $this->redirectToRoute('app_settings_subscription');
    }
}
