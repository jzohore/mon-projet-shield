<?php

declare(strict_types=1);

namespace App\Infrastructure\EventSubscriber;

use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OnboardingStatus;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

final class EnforceOnboardingSubscriber implements EventSubscriberInterface
{
    private const array ALLOWED_ROUTES = [
        'app_logout',               // Il faut toujours laisser le droit de se déconnecter !
        'app_login',                // Si jamais il tombe ici
        'app_register',
        'ux_live_component',
    ];

    public function __construct(
        private Security $security,
        private RouterInterface $router,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            // On écoute TOUTES les requêtes HTTP.
            // Priorité 0 : On s'assure de passer APRÈS le Firewall de Symfony (qui a une priorité de 8)
            // pour être sûr que l'utilisateur est bien identifié en session.
            KernelEvents::REQUEST => ['onKernelRequest', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $currentRoute = $request->attributes->get('_route');

        // On laisse passer les requêtes techniques de Symfony et les Live Components
        if (is_string($currentRoute) && str_starts_with($currentRoute, '_')) {
            return;
        }

        // On laisse passer les routes de sécurité (Login, Logout)
        if (in_array($currentRoute, self::ALLOWED_ROUTES, true)) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        // --- LA LOGIQUE DE ROUTAGE STRICTE DE L'ONBOARDING ---

        // 1. Si tout est fini, on ne fait rien, on laisse passer vers l'application !
        if ($user->onboardingStatus === OnboardingStatus::COMPLETED) {
            return;
        }

        // 2. Si l'utilisateur en est à l'ÉTAPE 1 (Espace de travail)
        if ($user->onboardingStatus === OnboardingStatus::PENDING) {
            if ($currentRoute !== 'app_onboarding_workspace') {
                $url = $this->router->generate('app_onboarding_workspace', [
                    'slugId' => $user->slugId,
                ]);
                $event->setResponse(new RedirectResponse($url));
            }
            return; // On arrête l'exécution ici
        }

        // 3. Si l'utilisateur en est à l'ÉTAPE 2 (Profil)
        if ($user->onboardingStatus === OnboardingStatus::WORKSPACE_SETUP) {
            if ($currentRoute !== 'app_onboarding_profile') {
                $url = $this->router->generate('app_onboarding_profile', [
                    'slugId' => $user->slugId,
                ]);
                $event->setResponse(new RedirectResponse($url));
            }
            return; // On arrête l'exécution ici
        }
    }
}
