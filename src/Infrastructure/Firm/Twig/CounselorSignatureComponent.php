<?php

declare(strict_types=1);

namespace App\Infrastructure\Firm\Twig;

use App\Application\Firm\UseCase\CurrentRegulatoryProfileInfo;
use App\Application\Firm\UseCase\UpdateCounselorSignatureUseCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'CounselorSignatureComponent',
    template: 'components/Firm/CounselorSignatureComponent.html.twig',
)]
class CounselorSignatureComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public ?string $signatureBase64 = null;

    public bool $isSaved = false;

    #[LiveProp(writable: true)]
    public bool $isEditing = false;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly UpdateCounselorSignatureUseCase $updateSignatureUseCase,
        private readonly LoggerInterface $logger,
        private readonly UrlGeneratorInterface $router,
        private readonly CurrentRegulatoryProfileInfo $profileInfo,
    ) {
    }

    public function mount(): void
    {
        $profile = ($this->profileInfo)();

        // On récupère la signature actuelle si elle existe
        $this->signatureBase64 = $profile->signatureBase64;

        // Si le conseiller n'a pas de signature, on ouvre le mode édition par défaut
        // S'il en a une, on restera en mode lecture (false)
        $this->isEditing = in_array($this->signatureBase64, [null, '', '0'], true);
    }

    #[LiveAction]
    public function activateEditing(): void
    {
        $this->isEditing = true;
    }

    public function hasSignature(): bool
    {
        return !in_array($this->signatureBase64, [null, '', '0'], true);
    }

    #[LiveAction]
    public function saveSignature(): ?RedirectResponse
    {
        if (in_array($this->signatureBase64, [null, '', '0'], true)) {
            /** @var FlashBagAwareSessionInterface $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add(
                type: 'error',
                message: 'Veuillez dessiner votre signature avant d\'enregistrer.',
            );

            return new RedirectResponse($this->router->generate('app_settings_regulatory_profile'));
        }

        try {
            ($this->updateSignatureUseCase)($this->signatureBase64);

            /** @var FlashBagAwareSessionInterface $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add(
                type: 'success',
                message: 'Votre signature a bien été mis à jour.'
            );

            return new RedirectResponse($this->router->generate('app_settings_regulatory_profile'));
        } catch (\DomainException $e) {
            $this->logger->error('Erreur lors de la sauvegarde de la signature.', [
                'error' => $e->getMessage(),
            ]);

            return null;
        } catch (\Exception $e) {
            $this->logger->critical('Crash système lors de la sauvegarde de la signature.', [
                'error' => $e->getMessage(),
            ]);

            /** @var FlashBagAwareSessionInterface $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add(
                type: 'error',
                message: 'Une erreur technique est survenue lors de l\'enregistrement. Veuillez réessayer.'
            );

            return new RedirectResponse($this->router->generate('app_settings_regulatory_profile'));
        }
    }
}
