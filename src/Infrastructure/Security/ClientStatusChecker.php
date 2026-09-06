<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\User\Entity\Client;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Contrôle de statut pour le firewall « portal ». Un compte client désactivé
 * (relation d'affaires clôturée, partenariat suspendu) ne doit plus pouvoir
 * accéder à son espace, même avec un magic link encore valide.
 */
final class ClientStatusChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Client) {
            return;
        }

        if (!$user->isActif) {
            throw new CustomUserMessageAccountStatusException('Votre accès a été clôturé par votre conseiller. Contactez-le si vous pensez qu\'il s\'agit d\'une erreur.');
        }
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        // Rien à vérifier après authentification.
    }
}
