<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/**
 * @see https://symfony.com/doc/current/security/custom_authenticator.html
 */
class MagicLinkAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request): ?bool
    {
        return 'app_verify_magic_link' === $request->attributes->get('_route')
            && $request->attributes->has('token');
    }

    // src/Security/MagicLinkAuthenticator.php (méthode authenticate)

    public function authenticate(Request $request): Passport
    {
        $token = $request->attributes->get('token');

        if (! $token) {
            throw new AuthenticationException('Jeton de connexion manquant.');
        }

        // Le jeton est le seul identifiant. On charge l'utilisateur via le jeton.
        // NOTE : Cela nécessite que votre UserProvider gère la recherche par jeton
        // ou que vous le fassiez manuellement via un service/repository.

        // On utilise un CustomUserProvider (similaire au UserBadge mais par Token)
        $userLoader = function ($token) {
            // --- C'est ici que l'utilisateur est chargé par le jeton ---
            $user = $this->userRepository->findByMagicLink($token);

            if (! $user) {
                throw new AuthenticationException('Jeton de connexion invalide ou déjà utilisé.');
            }

            return $user;
        };

        $tokenBadge = new CustomCredentials(
            function ($credentials, UserInterface $user): bool {
                /** @var User $user */
                // 1. Le jeton correspond (double vérification, car il est déjà chargé par le jeton)
                if ($credentials !== $user->magicLinkToken) {
                    return false;
                }

                // 2. CORRECTION CRITIQUE de la LOGIQUE D'EXPIRATION
                if (! $user->isMagicLinkTokenValid()) {
                    // Le jeton a expiré ou est invalide
                    return false;
                }

                return true;
            },
            $token
        );

        return new Passport(
            new UserBadge($token, $userLoader),
            $tokenBadge,
            []
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        // on success, let the request continue
        return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    // public function start(Request $request, ?AuthenticationException $authException = null): Response
    // {
    //     /*
    //      * If you would like this class to control what happens when an anonymous user accesses a
    //      * protected page (e.g. redirect to /login), uncomment this method and make this class
    //      * implement Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface.
    //      *
    //      * For more details, see https://symfony.com/doc/current/security/experimental_authenticators.html#configuring-the-authentication-entry-point
    //      */
    // }
}
