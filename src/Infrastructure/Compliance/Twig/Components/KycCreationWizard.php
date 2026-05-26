<?php

namespace App\Infrastructure\Compliance\Twig\Components;

use App\Application\Compliance\DTO\Response\DraftFolderResponse;
use App\Application\Compliance\UseCase\CreateDraftFolderUseCase;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Shared\Exception\AbstractDomainException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;

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
        private readonly ComplianceFolderRepositoryInterface $complianceFolderRepository,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
    ) {}
    #[LiveProp]
    public ?string $entityType = null;

    #[LiveAction]
    public function selectType(#[LiveArg] string $type): void
    {
        // Quand on clique, on enregistre le choix, ce qui déclenche l'affichage du reste
        $this->entityType = $type;
    }

    #[LiveAction]
    public function selectMethod(#[LiveArg] string $method): RedirectResponse
    {
        if (!$this->entityType) {
            $this->logger->warning('Tentative d\'accès à selectMethod sans entityType.', [
                'method' => $method,
            ]);

            throw new \LogicException('Un type d\'entité doit être sélectionné en premier.');
        }

        try {
            $isCurrentDraft = $this->complianceFolderRepository->findOneLastDraftIndividuals();

            if ($isCurrentDraft) {
                $responseDto = DraftFolderResponse::fromEntity($isCurrentDraft);

                $this->logger->info('Reprise d\'un dossier brouillon existant.', [
                    'slugId' => $responseDto->slugId,
                    'entityType' => $this->entityType,
                    'method' => $method,
                ]);
            } else {
                $responseDto = ($this->createDraftFolderUseCase)($this->entityType);

                $this->logger->info('Création d\'un nouveau dossier brouillon.', [
                    'slugId' => $responseDto->slugId,
                    'entityType' => $this->entityType,
                    'method' => $method,
                ]);
            }

            return new RedirectResponse($this->urlGenerator->generate('app_compliance_method_new', [
                'type' => $this->entityType,
                'method' => $method,
                'slugId' => $responseDto->slugId,
            ]));

        } catch (AbstractDomainException $e) {
            // Log de niveau ERROR pour les exceptions métier connues
            $this->logger->error('Erreur métier lors de l\'initialisation du dossier de conformité.', [
                'exception_message' => $e->getMessage(),
                'entityType' => $this->entityType,
                'method' => $method,
                'trace' => $e->getTraceAsString(),
            ]);

            /** @var FlashBagAwareSessionInterface $session */
            // Option 1 : On laisse remonter l'exception métier pour qu'elle soit gérée plus haut
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add(
                'error',
                $e->getMessage(),
            );
            return new RedirectResponse($this->urlGenerator->generate('app_compliance_new'));
        } catch (\Exception $e) {
            // Log CRITICAL corrigé (plus de variable fantôme liée à un collaborateur)
            $this->logger->critical('Crash système lors de la préparation du dossier de conformité.', [
                'error' => $e->getMessage(),
                'entityType' => $this->entityType,
                'method' => $method,
                'trace' => $e->getTraceAsString(),
            ]);

            // Récupération sécurisée de la session pour ajouter le FlashMessage
            try {
                /** @var FlashBagAwareSessionInterface $session */
                $session = $this->requestStack->getSession();
                $session->getFlashBag()->add(
                    'error',
                    'Une erreur technique est survenue lors de la préparation de votre dossier.',
                );
            } catch (SessionNotFoundException) {
                // Sécurité au cas où la session n'est pas disponible (ex: appel console ou stateless)
            }

            return new RedirectResponse($this->urlGenerator->generate('app_compliance_list'));
        }
    }
}
