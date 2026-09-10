<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\App\Support;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Point d'entrée unique de la connexion support (impersonation d'un CGP).
 * Impose un motif ; le SwitchUserAuditListener refuse tout switch sans motif
 * en session et trace l'entrée / la sortie dans le journal du cabinet.
 */
#[AsController]
#[IsGranted('ROLE_SUPPORT')]
#[Route(path: '/app/support/impersonate', name: 'app_support_impersonate', methods: ['GET', 'POST'])]
final class StartImpersonationController extends AbstractController
{
    private const string SESSION_REASON_KEY = '_support_impersonation_reason';

    public function __invoke(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->handle($request);
        }

        return $this->render('@app/support/start_impersonation.html.twig', [
            'page_title' => 'Connexion support',
            'target_email' => (string) $request->query->get('email', ''),
        ]);
    }

    private function handle(Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('support-impersonate', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('app_support_impersonate');
        }

        $email = trim((string) $request->request->get('target_email'));
        $reason = trim((string) $request->request->get('reason'));

        if ('' === $email || false === filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Adresse e-mail du collaborateur invalide.');

            return $this->redirectToRoute('app_support_impersonate', ['email' => $email]);
        }

        if (mb_strlen($reason) < 10) {
            $this->addFlash('error', 'Le motif d\'accès doit faire au moins 10 caractères.');

            return $this->redirectToRoute('app_support_impersonate', ['email' => $email]);
        }

        $request->getSession()->set(self::SESSION_REASON_KEY, $reason);

        return new RedirectResponse('/?_switch_user=' . rawurlencode($email));
    }
}
