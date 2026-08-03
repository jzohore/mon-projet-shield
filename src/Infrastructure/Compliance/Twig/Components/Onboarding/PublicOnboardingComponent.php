<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Twig\Components\Onboarding;

use App\Application\Compliance\DTO\Request\SetIndividualClientRequest;
use App\Application\Compliance\DTO\Response\ComplianceFolderShowResponse;
use App\Application\Compliance\UseCase\ComplianceDocument\AddDocumentUseCase;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\GenerateDerUseCase;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\SendDerToClientUseCase;
use App\Application\Compliance\UseCase\ComplianceFolder\ComplianceFolderShowAssembler;
use App\Application\Compliance\UseCase\ComplianceFolder\SetIndividualClientUseCase;
use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\Compliance\Entity\ComplianceDocument;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Entity\IndividualFolder;
use App\Domain\Compliance\Enum\DocumentType;
use App\Domain\Compliance\Enum\FolderType;
use App\Domain\Compliance\Factory\ComplianceFolderFactory;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\Compliance\Service\DocumentRequirementEngine;
use App\Domain\Shared\Exception\AbstractDomainException;
use App\Domain\Workspace\Entity\Workspace;
use App\Infrastructure\Compliance\Form\SetIndividualClientType;
use App\Infrastructure\DocuSeal\DocuSealClient;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'PublicOnboardingComponent',
    template: 'components/Compliance/Onboarding/PublicOnboardingComponent.html.twig',
)]
class PublicOnboardingComponent extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp(writable: false)]
    public Workspace $workspace;

    #[LiveProp(writable: true)]
    public ?ComplianceFolder $complianceFolder = null;

    #[LiveProp(writable: true, url: true)]
    public ?string $folderType = null;

    #[LiveProp(url: true)]
    public bool $isEditing = true;

    #[LiveProp]
    public bool $isFinished = false;

    public function __construct(
        private readonly SetIndividualClientUseCase $setIndividualUseCase,
        private readonly ComplianceFolderShowAssembler $complianceFolderShowAssembler,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
        private readonly ComplianceFolderRepositoryInterface $repository,
        private readonly ComplianceFolderFactory $folderFactory,
        private readonly DocumentRequirementEngine $requirementEngine,
        // eventDispatcher retiré car jamais lu
        private readonly ComplianceDocumentRepositoryInterface $documentRepository,
        private readonly AddDocumentUseCase $addDocumentUseCase,
        private readonly GenerateDerUseCase $generateDerUseCase,
        private readonly DocuSealClient $documentSealClient,
        private readonly SendDerToClientUseCase $sendDerUseCase,
    ) {
    }

    public function mount(Workspace $workspace): void
    {
        $this->workspace = $workspace;

        $session = $this->requestStack->getSession();
        $draftId = $session->get('flash_draft_folder_id');

        if (is_string($draftId) || is_int($draftId)) {
            $folder = $this->repository->findById((string) $draftId);

            // Guard : On vérifie que le dossier est valide et appartient au bon cabinet
            if ($folder->workspace->id === $workspace->id) {
                $this->complianceFolder = $folder;

                if ($this->complianceFolder instanceof IndividualFolder) {
                    $this->folderType = FolderType::INDIVIDUAL->value;
                    $this->isEditing = in_array($this->complianceFolder->email, [null, '', '0'], true);
                } elseif ($this->complianceFolder instanceof BusinessFolder) {
                    $this->folderType = FolderType::BUSINESS->value;
                    // Logique fallback pour un dossier pro
                    $this->isEditing = true;
                }
            }
        }
    }

    public function getComplianceDTO(): ?ComplianceFolderShowResponse
    {
        if (!$this->complianceFolder instanceof ComplianceFolder) {
            return null;
        }

        return $this->complianceFolderShowAssembler->assemble($this->complianceFolder);
    }

    #[LiveAction]
    public function selectType(
        #[LiveArg] string $type,
    ): void {
        try {
            Assert::inArray($type, ['individual', 'company'], 'Type d\'entité invalide.');

            if (!$this->complianceFolder instanceof ComplianceFolder) {
                $enumType = 'individual' === $type ? FolderType::INDIVIDUAL : FolderType::BUSINESS;

                $this->complianceFolder = $this->folderFactory->createDraft(
                    type: $enumType,
                    workspace: $this->workspace,
                    email: 'anonymous@flash.kysure.local',
                    method: 'flash_qr_code'
                );

                $this->requirementEngine->generateBaseRequirements($this->complianceFolder);
                $this->repository->save($this->complianceFolder);

                Assert::notNull($this->complianceFolder->id);
                $this->requestStack->getSession()->set('flash_draft_folder_id', $this->complianceFolder->id->toString());
            }

            $this->folderType = $type;
            $this->isEditing = true;
        } catch (\Exception $e) {
            $this->logger->critical('Crash système lors de la préparation du dossier Flash.', [
                'error' => $e->getMessage(),
            ]);
            $this->addFlash('error', 'Une erreur technique est survenue.');
        }
    }

    #[LiveAction]
    public function resetType(): void
    {
        $this->folderType = null;
        $this->isEditing = true;
        $this->complianceFolder = null;
        $this->requestStack->getSession()->remove('flash_draft_folder_id');
    }

    #[LiveAction]
    public function activateEditing(): void
    {
        $this->isEditing = true;
    }

    protected function instantiateForm(): FormInterface
    {
        if (null === $this->folderType) {
            return $this->createFormBuilder()->getForm();
        }

        if ('individual' === $this->folderType) {
            $request = new SetIndividualClientRequest();

            if ($this->complianceFolder instanceof ComplianceFolder) {
                $request->reference = $this->complianceFolder->reference;
            }

            if ($this->complianceFolder instanceof IndividualFolder) {
                $request->firstName = $this->complianceFolder->firstName ?? '';
                $request->lastName = $this->complianceFolder->lastName ?? '';
                $request->email = $this->complianceFolder->email ?? '';
            }

            return $this->createForm(SetIndividualClientType::class, $request);
        }

        return $this->createFormBuilder()->getForm();
    }

    #[LiveAction]
    public function saveDraft(): void
    {
        $this->submitForm();

        // Guard défensif pour l'analyse statique
        Assert::notNull($this->complianceFolder, 'Action impossible sans dossier actif.');

        /** @var SetIndividualClientRequest $request */
        $request = $this->getForm()->getData();

        try {
            $request->reference = $this->complianceFolder->reference;

            if ('individual' === $this->folderType) {
                ($this->setIndividualUseCase)($request);
            }

            $this->isEditing = false;
        } catch (\Exception $e) {
            $this->logger->error('Erreur Flash Onboarding (Save)', ['error' => $e->getMessage()]);
            $this->addFlash('error', 'Erreur lors de la sauvegarde. Veuillez réessayer.');
        }
    }

    #[LiveAction]
    public function confirmAndSign(): ?RedirectResponse
    {
        // Guard défensif critique
        Assert::notNull($this->complianceFolder, 'Session expirée ou dossier introuvable.');

        try {
            $document = $this->documentRepository->findDerByFolder($this->complianceFolder);

            if (!$document instanceof ComplianceDocument) {
                $document = ($this->addDocumentUseCase)(DocumentType::DER, $this->complianceFolder);
            }

            Assert::notNull($document->id, 'L\'ID du document ne peut pas être nul.');

            ($this->generateDerUseCase)(documentId: $document->id->toString(), folder: $this->complianceFolder);

            $this->logger->info('Génération du DER lancée depuis le Flash Onboarding.', [
                'folder_id' => $this->complianceFolder->id,
                'document_id' => $document->id->toString(),
            ]);

            $dto = $this->getComplianceDTO();
            Assert::notNull($dto, 'Impossible de générer le résumé du dossier.');

            // Sécurité typage pour l'API externe DocuSeal
            $clientEmail = $dto->contactEmail;
            $clientName = $dto->contactName;

            Assert::stringNotEmpty($clientEmail, 'L\'email du contact est obligatoire pour la signature.');
            Assert::stringNotEmpty($clientName, 'Le nom du contact est obligatoire pour la signature.');

            $result = $this->documentSealClient->createSignatureRequest(
                $clientEmail,
                $clientName,
            );

            return new RedirectResponse($result['url']);
        } catch (AbstractDomainException $e) {
            $this->logger->error('Erreur métier lors de la génération du DER (Flash)', [
                'folder_id' => $this->complianceFolder->id,
                'error' => $e->getMessage(),
            ]);
            $this->addFlash('error', $e->getMessage());

            return null;
        } catch (\Exception $e) {
            $this->logger->critical('Crash système lors de la génération du DER (Flash)', [
                'folder_id' => $this->complianceFolder->id,
                'error' => $e->getMessage(),
            ]);
            $this->addFlash('error', 'Une erreur technique est survenue lors de la préparation de votre document.');

            return null;
        }
    }

    #[LiveAction]
    public function confirmAndSendLater(): void
    {
        // Guard défensif
        Assert::notNull($this->complianceFolder, 'Session expirée ou dossier introuvable.');

        try {
            $document = $this->documentRepository->findDerByFolder($this->complianceFolder);

            if (!$document instanceof ComplianceDocument) {
                $document = ($this->addDocumentUseCase)(DocumentType::DER, $this->complianceFolder);
            }

            Assert::notNull($document->id);

            ($this->generateDerUseCase)(documentId: $document->id->toString(), folder: $this->complianceFolder);
            ($this->sendDerUseCase)($this->complianceFolder);

            $this->logger->info('Validation Flash asynchrone : Email de signature programmé.', [
                'folder_id' => $this->complianceFolder->id,
            ]);

            $this->requestStack->getSession()->remove('flash_draft_folder_id');
            $this->isFinished = true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la validation asynchrone.', ['error' => $e->getMessage()]);
            $this->addFlash('error', 'Une erreur est survenue lors de la préparation de votre dossier.');
        }
    }
}
