<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Twig\Components;

use App\Application\Compliance\UseCase\ComplianceFolder\CreateDraftFolderUseCase;
use App\Domain\Shared\Exception\AbstractDomainException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'KycCreationWizard',
    template: 'components/Compliance/KycCreationWizard.html.twig',
)]
class KycCreationWizard
{
    use DefaultActionTrait;

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CreateDraftFolderUseCase $createDraftFolderUseCase,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[LiveAction]
    public function selectMethod(#[LiveArg] string $type, #[LiveArg] string $method): RedirectResponse
    {
        // 🪄 1. Validation défensive stricte (Guard Pattern)
        try {
            Assert::inArray($type, ['individual', 'business'], 'Type d\'entité invalide.');
            Assert::inArray($method, ['request', 'manual'], 'Méthode de création invalide.');
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Tentative d\'accès à selectMethod avec des arguments altérés.', [
                'type' => $type,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
            throw new \LogicException('Les paramètres fournis sont invalides.', $e->getCode(), $e);
        }

        try {
            $responseDto = ($this->createDraftFolderUseCase)($type, $method);

            $this->logger->info('Création d\'un nouveau dossier brouillon.', [
                'slugId' => $responseDto->slugId,
                'entityType' => $type,
                'method' => $method,
            ]);

            // 🪄 4. Redirection vers la prochaine étape
            return new RedirectResponse($this->urlGenerator->generate('app_compliance_method_new', [
                'type' => $type,
                'method' => $method,
                'slugId' => $responseDto->slugId,
            ]));
        } catch (AbstractDomainException $e) {
            $this->logger->error('Erreur métier lors de l\'initialisation du dossier de conformité.', [
                'exception_message' => $e->getMessage(),
                'entityType' => $type,
                'method' => $method,
            ]);

            $this->addFlashMessage('error', $e->getMessage());

            return new RedirectResponse($this->urlGenerator->generate('app_compliance_new'));
        } catch (\Exception $e) {
            $this->logger->critical('Crash système lors de la préparation du dossier de conformité.', [
                'error' => $e->getMessage(),
                'entityType' => $type,
                'method' => $method,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addFlashMessage('error', 'Une erreur technique est survenue lors de la préparation de votre dossier.');

            return new RedirectResponse($this->urlGenerator->generate('app_compliance_list'));
        }
    }

    /**
     * 🛠 Utilitaire privé pour sécuriser l'accès à la session.
     */
    private function addFlashMessage(string $type, string $message): void
    {
        try {
            $session = $this->requestStack->getSession();
            if (method_exists($session, 'getFlashBag')) {
                $session->getFlashBag()->add($type, $message);
            }
        } catch (SessionNotFoundException) {
            // Silencieux : Prévient les erreurs si le contexte est stateless (ex: appels API purs)
        }
    }
}
