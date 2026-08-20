<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Twig\Components;

use App\Application\Compliance\UseCase\ComplianceFolder\MarkAsAcceptedRecordingUseCase;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Shared\Exception\AbstractDomainException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'AcceptRecordingTwigComponent',
    template: 'components/Compliance/AcceptRecordingTwigComponent.html.twig',
)]
class AcceptRecordingTwigComponent extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp(writable: false)]
    public ComplianceFolder $complianceFolder;

    #[LiveProp]
    public string $type;

    #[LiveProp]
    public string $method;

    public string $slug;

    public function __construct(
        private readonly MarkAsAcceptedRecordingUseCase $useCase,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[LiveListener('clientSaved')]
    public function onClientSaved(): void
    {
        // Force refresh
    }

    public function isAcceptRecording(): bool
    {
        return $this->complianceFolder->isAcceptRecording;
    }

    public function isStepBannerVisible(): bool
    {
        return !empty($this->complianceFolder->email);
    }

    #[LiveAction]
    public function save(): RedirectResponse
    {
        try {
            ($this->useCase)($this->complianceFolder);

            $this->addFlash(
                'success',
                'Vous pouvez lancez l\'enregistrement.'
            );
        } catch (AbstractDomainException $e) {
            $this->logger->warning('Tentative d\'envoie du DER bloquée.', [
                'folder_slug' => $this->complianceFolder->slugId,
                'error' => $e->getMessage(),
            ]);

            $this->addFlash(
                'error',
                $e->getMessage()
            );
        } catch (\Exception $e) {
            $this->logger->error('Crash système lors de l\'envoi du DER.', [
                'folder_slug' => $this->complianceFolder->slugId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addFlash(
                'error',
                'Une erreur système est survenue lors de l\'enregistrement.',
            );
        }

        return $this->redirectToRoute('app_compliance_method_new', [
            'type' => $this->type,
            'method' => $this->method,
            'slugId' => $this->complianceFolder->slugId,
        ]);
    }
}
