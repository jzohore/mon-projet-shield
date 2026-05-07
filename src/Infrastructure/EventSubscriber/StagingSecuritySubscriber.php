<?php

namespace App\Infrastructure\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class StagingSecuritySubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire('%kernel.environment%')]
        private string $appEnv
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            // Priorité très haute (1000) pour intercepter la requête AVANT le routeur et la sécurité Symfony
            KernelEvents::REQUEST => ['onKernelRequest', 1000],
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // 1. On ne bloque jamais la vraie Production
        if ($this->appEnv === 'prod') {
            return;
        }

        // 2. On ignore les sous-requêtes internes à Symfony
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // 3. 🛡️ L'avantage absolu : On laisse un trou dans le bouclier pour les bots externes légitimes !
        if (str_starts_with($request->getPathInfo(), '/webhook/stripe')) {
            return;
        }

        // 4. Vérification des identifiants (récupérés depuis la fenêtre de connexion du navigateur)
        $user = $request->headers->get('php-auth-user');
        $pass = $request->headers->get('php-auth-pw');

        // Remplace par tes propres codes secrets
        $expectedUser = 'kysure_team';
        $expectedPass = 'StagingSecure2026!';

        if ($user !== $expectedUser || $pass !== $expectedPass) {
            // Si c'est faux, on renvoie une erreur 401 qui force le navigateur à (ré)afficher la fenêtre de mot de passe
            $response = new Response('Accès restreint au Staging', Response::HTTP_UNAUTHORIZED, [
                'WWW-Authenticate' => 'Basic realm="Kysure Staging Area"',
            ]);
            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        // On garde notre protection SEO
        if ($this->appEnv !== 'prod') {
            $event->getResponse()->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }
    }
}
