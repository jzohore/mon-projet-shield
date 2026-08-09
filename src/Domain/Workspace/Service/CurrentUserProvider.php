<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Service;

use App\Domain\User\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Webmozart\Assert\Assert;

readonly class CurrentUserProvider
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function getUser(): User
    {
        $user = $this->security->getUser();

        // On garantit au reste de l'application que l'utilisateur est valide
        Assert::notNull($user, 'Aucun utilisateur connecté.');
        Assert::isInstanceOf($user, User::class, 'Type d\'utilisateur invalide.');

        return $user;
    }

    public function isAuthenticated(): bool
    {
        return $this->security->getUser() instanceof User;
    }
}
