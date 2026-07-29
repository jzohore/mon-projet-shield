<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Twig\Components\Onboarding;

use App\Application\Compliance\DTO\Response\ComplianceFolderShowResponse;
use App\Application\Compliance\UseCase\ComplianceDocument\AddDocumentUseCase;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\CreateDocusealSignatureRequestUseCase;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\GenerateDerUseCase;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\SaveDocuSealUrlUseCase;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\SendDerToClientUseCase;
use App\Application\Compliance\UseCase\ComplianceFolder\ComplianceFolderShowAssembler;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Enum\DocumentType;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Shared\Exception\AbstractDomainException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponder;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'FlashStep3SignComponent',
    template: 'components/Compliance/Onboarding/FlashStep3SignComponent.html.twig',
)]
final class FlashStep3SignComponent extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public bool $isVisible = false;

    #[LiveProp]
    public bool $isFinished = false;

    private ?ComplianceFolder $complianceFolder = null;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ComplianceFolderRepositoryInterface $repository,
        private readonly ComplianceFolderShowAssembler $assembler,
        private readonly LoggerInterface $logger,
        private readonly GenerateDerUseCase $generateDerUseCase,
        private readonly AddDocumentUseCase $addDocumentUseCase,
        private readonly ComplianceDocumentRepositoryInterface $complianceDocumentRepository,
        private readonly SendDerToClientUseCase $sendDerToClientUseCase,
        private readonly CreateDocusealSignatureRequestUseCase $createDocusealSignatureRequestUseCase,
        private readonly SaveDocuSealUrlUseCase $saveDocuSealUrlUseCase,
        private readonly LiveResponder $responder,
    ) {
    }

    public function mount(): void
    {
        $this->loadFolderFromSession();
        if ($this->complianceFolder instanceof ComplianceFolder && !$this->complianceFolder->isDraftEmpty()) {
            $this->isVisible = true;
        }
    }

    #[LiveListener('formSaved')]
    public function showSummary(): void
    {
        $this->loadFolderFromSession();
        $this->isVisible = true;
    }

    private function loadFolderFromSession(): void
    {
        $draftId = $this->requestStack->getSession()->get('flash_draft_folder_id');
        if (is_string($draftId) || is_int($draftId)) {
            $this->complianceFolder = $this->repository->findById((string) $draftId);
        }
    }

    public function getComplianceDTO(): ?ComplianceFolderShowResponse
    {
        if (!$this->complianceFolder instanceof ComplianceFolder) {
            return null;
        }

        return $this->assembler->assemble($this->complianceFolder);
    }

    #[LiveAction]
    public function activateEditing(): void
    {
        $this->isVisible = false;
        $this->responder->emit('backToForm');
        // Réveille l'Étape 2
    }

    #[LiveAction]
    public function confirmAndSign(): ?RedirectResponse
    {
        $this->loadFolderFromSession();
        try {
            Assert::notNull($this->complianceFolder, 'Dossier introuvable.');

            $document = $this->complianceDocumentRepository->findDerByFolder($this->complianceFolder);
            if (!$document instanceof \App\Domain\Compliance\Entity\ComplianceDocument) {
                $document = ($this->addDocumentUseCase)(DocumentType::DER, $this->complianceFolder);
            }
            Assert::notNull($document->id);

            ($this->generateDerUseCase)(documentId: (string) $document->id, folder: $this->complianceFolder);

            // 🪄 Utilisation du nouveau UseCase isolé !
            $docuSealInfo = ($this->createDocusealSignatureRequestUseCase)($this->complianceFolder);

            ($this->saveDocuSealUrlUseCase)($document, $docuSealInfo['url'], (int) $docuSealInfo['id']);
            $this->requestStack->getSession()->remove('flash_draft_folder_id');

            return new RedirectResponse($docuSealInfo['url']);
        } catch (AbstractDomainException $e) {
            $this->addFlash('error', $e->getMessage());

            return null;
        } catch (\Exception $e) {
            $this->logger->critical('Crash Flash Step 3', ['error' => $e->getMessage()]);
            $this->addFlash('error', 'Erreur technique lors de la préparation.');

            return null;
        }
    }

    #[LiveAction]
    public function confirmAndSendLater(): void
    {
        $this->loadFolderFromSession();
        try {
            Assert::notNull($this->complianceFolder);
            $document = $this->complianceDocumentRepository->findDerByFolder($this->complianceFolder);
            if (!$document instanceof \App\Domain\Compliance\Entity\ComplianceDocument) {
                $document = ($this->addDocumentUseCase)(DocumentType::DER, $this->complianceFolder);
            }
            Assert::notNull($document->id);

            ($this->generateDerUseCase)(documentId: (string) $document->id, folder: $this->complianceFolder);
            ($this->sendDerToClientUseCase)($this->complianceFolder);

            $this->requestStack->getSession()->remove('flash_draft_folder_id');
            $this->isFinished = true;
        } catch (\Exception) {
            $this->addFlash('error', 'Erreur lors de la validation asynchrone.');
        }
    }
}
