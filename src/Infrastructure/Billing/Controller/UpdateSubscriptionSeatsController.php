<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Controller;

use App\Application\Billing\UseCase\Subscription\UpdateSubscriptionSeatsUseCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[IsGranted('ROLE_USER')]
#[Route(path: '/app/billing/seats', name: 'app_billing_seats', methods: ['POST'])]
#[IsCsrfTokenValid('billing_seats')]
final readonly class UpdateSubscriptionSeatsController
{
    public function __construct(
        private UpdateSubscriptionSeatsUseCase $updateSubscriptionSeats,
        private UrlGeneratorInterface $urlGenerator,
        private RequestStack $requestStack,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $seats = (int) $request->request->get('seats', 0);
        $back = new RedirectResponse($this->urlGenerator->generate('app_settings_subscription'));

        try {
            ($this->updateSubscriptionSeats)($seats);
            $this->flash('success', 'Le nombre de sièges a été mis à jour.');
        } catch (\DomainException $e) {
            $this->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error('Échec de mise à jour des sièges : ' . $e->getMessage());
            $this->flash('error', 'La mise à jour des sièges a échoué. Réessayez dans un instant.');
        }

        return $back;
    }

    private function flash(string $type, string $message): void
    {
        $session = $this->requestStack->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add($type, $message);
        }
    }
}
