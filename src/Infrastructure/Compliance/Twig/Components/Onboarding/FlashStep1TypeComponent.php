<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Twig\Components\Onboarding;

use App\Application\Compliance\UseCase\ComplianceFolder\DeleteComplianceFolderUseCase;
use App\Domain\Compliance\Enum\FolderType;
use App\Domain\Compliance\Factory\ComplianceFolderFactory;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Compliance\Service\DocumentRequirementEngine;
use App\Domain\Workspace\Entity\Workspace;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponder;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'FlashStep1TypeComponent',
    template: 'components/Compliance/Onboarding/FlashStep1TypeComponent.html.twig',
)]
final class FlashStep1TypeComponent extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp(writable: false)]
    public Workspace $workspace;

    #[LiveProp]
    public bool $isVisible = true; // S'affiche par défaut

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ComplianceFolderRepositoryInterface $repository,
        private readonly LoggerInterface $logger,
        private readonly DeleteComplianceFolderUseCase $deleteFolderUseCase,
        private readonly ComplianceFolderFactory $folderFactory,
        private readonly DocumentRequirementEngine $requirementEngine,
        private readonly LiveResponder $responder,
    ) {
    }

    public function mount(Workspace $workspace): void
    {
        $this->workspace = $workspace;

        $draftId = $this->requestStack->getSession()->get('flash_draft_folder_id');

        // Si un dossier existe déjà en session, on masque cette étape !
        if (is_string($draftId) || is_int($draftId)) {
            $this->isVisible = false;
        }
    }

    #[LiveAction]
    public function selectType(
        #[LiveArg] string $type,
    ): void {
        try {
            Assert::inArray($type, ['individual', 'company'], 'Type d\'entité invalide.');

            $enumType = 'individual' === $type ? FolderType::INDIVIDUAL : FolderType::BUSINESS;

            $folder = $this->folderFactory->createDraft(
                type: $enumType,
                workspace: $this->workspace,
                email: 'anonymous@flash.kysure.local',
                method: 'flash_qr_code'
            );

            $this->requirementEngine->generateBaseRequirements($folder);
            $this->repository->save($folder);

            // On stocke en session
            $this->requestStack->getSession()->set('flash_draft_folder_id', (string) $folder->id);

            // On se masque et on prévient l'Étape 2 de s'afficher !
            $this->isVisible = false;
            $this->responder->emit('draftCreated');
        } catch (\Exception $e) {
            $this->logger->critical('Crash Flash Step 1', ['error' => $e->getMessage()]);
            $this->addFlash('error', 'Une erreur technique est survenue.');
        }
    }

    /**
     * 🪄 Écoute le bouton "Retour au choix" de l'Étape 2.
     */
    #[LiveListener('resetToStep1')]
    public function onReset(): void
    {
        $complianceFolderId = $this->requestStack->getSession()->get('flash_draft_folder_id');
        $folder = $this->repository->findById($complianceFolderId);
        ($this->deleteFolderUseCase)($folder);
        $this->requestStack->getSession()->remove('flash_draft_folder_id');
        $this->isVisible = true;
    }
}
