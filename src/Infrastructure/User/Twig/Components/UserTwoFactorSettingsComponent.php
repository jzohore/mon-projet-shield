<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Twig\Components;

use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'UserTwoFactorSettingsComponent',
    template: 'components/User/UserTwoFactorSettingsComponent.html.twig',
)]
class UserTwoFactorSettingsComponent
{
    use DefaultActionTrait;

    public function __construct(
        private readonly CurrentUserProvider $currentUserProvider,
        private readonly EntityManagerInterface $em,
        private readonly GoogleAuthenticatorInterface $googleAuthenticator,
        private readonly CurrentWorkspaceProvider $currentWorkspaceProvider,
    ) {
    }

    #[LiveProp]
    public bool $isSetupMode = false;

    #[LiveProp(writable: true)]
    public string $verificationCode = '';

    #[LiveProp]
    public ?string $errorMessage = null;

    public function is2faEnabled(): bool
    {
        $user = $this->currentUserProvider->getUser();

        // Optionnel : Tu peux ajouter une propriété isTotpVerified dans ton entité User pour être plus précis
        return null !== $user->getGoogleAuthenticatorSecret() && !$this->isSetupMode;
    }

    #[LiveAction]
    public function initiateSetup(): void
    {
        $user = $this->currentUserProvider->getUser();

        if (!$user->getGoogleAuthenticatorSecret()) {
            $user->setGoogleAuthenticatorSecret($this->googleAuthenticator->generateSecret());
            $this->em->flush();
        }

        $this->isSetupMode = true;
        $this->errorMessage = null;
    }

    #[LiveAction]
    public function confirmSetup(): void
    {
        $user = $this->currentUserProvider->getUser();
        $workspace = $this->currentWorkspaceProvider->getWorkspace();
        if ($this->googleAuthenticator->checkCode($user, $this->verificationCode)) {
            // ✅ SUCCÈS : Le code est bon, on valide définitivement !
            $user->setIsTotpVerified(true);
            $workspace->claimTwoFactorBonus();
            $this->em->flush();

            $this->isSetupMode = false;
            $this->verificationCode = '';
            $this->errorMessage = null;
        } else {
            $this->errorMessage = 'Le code saisi est incorrect. Veuillez réessayer.';
        }
    }

    #[LiveAction]
    public function disable(): void
    {
        $user = $this->currentUserProvider->getUser();

        // On supprime le secret pour désactiver le 2FA
        $user->setGoogleAuthenticatorSecret(null);
        $user->setIsTotpVerified(false);
        $this->em->flush();

        $this->isSetupMode = false;
    }
}
