<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final readonly class PortalLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private UrlGeneratorInterface $router,
    ) {
    }

    /**
     * Cette méthode est appelée automatiquement par Symfony
     * juste après que le lien magique ait été validé avec succès.
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        // On génère l'URL du tableau de bord client (assure-toi que le nom de la route correspond bien à ton code)

        $dashboardUrl = $this->router->generate('client_dashboard');

        // On redirige le client de manière fluide
        return new RedirectResponse($dashboardUrl);
    }
}
