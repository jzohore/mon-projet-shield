<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\User\Entity\Admin;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Contrôle de statut pour le firewall « admin ». Un compte de l'équipe KYSURE
 * suspendu ou archivé ne peut plus se connecter, même avec un magic link encore
 * valide. La coupure des sessions déjà ouvertes est assurée séparément par
 * l'empreinte de session (Admin::isEqualTo()).
 */
final class AdminStatusChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Admin) {
            return;
        }

        if ($user->isArchived) {
            throw new CustomUserMessageAccountStatusException('Ce compte a été fermé. Contactez un administrateur de la plateforme.');
        }

        if (!$user->isActif) {
            throw new CustomUserMessageAccountStatusException('Votre accès au back-office a été suspendu. Contactez un administrateur de la plateforme.');
        }
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        // Rien à vérifier après authentification.
    }
}
