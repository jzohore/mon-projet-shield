<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Listener;

use App\Domain\User\Event\UserOnboardingCompletedEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class RefreshUserSessionListener
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function __invoke(UserOnboardingCompletedEvent $event): void
    {
        // Magie Symfony 6.2+ : on reconnecte l'utilisateur en silence
        // pour mettre à jour son jeton de sécurité avec ses nouveaux rôles (ROLE_WORKSPACE_ADMIN)
        $this->security->login($event->user, 'security.authenticator.form_login.main', 'main');
    }
}
