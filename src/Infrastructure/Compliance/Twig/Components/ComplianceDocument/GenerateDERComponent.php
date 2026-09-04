<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Twig\Components\ComplianceDocument;

use App\Application\Compliance\DTO\Response\ComplianceFolderShowResponse;
use App\Application\Compliance\UseCase\ComplianceDocument\AddDocumentUseCase;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\GenerateDerUseCase;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\RevokeDerAcknowledgementUseCase;
use App\Application\Compliance\UseCase\ComplianceFolder\ComplianceFolderShowAssembler;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Entity\DerAcknowledgement;
use App\Domain\Compliance\Enum\DocumentType;
use App\Domain\Compliance\Repository\ComplianceDocumentRepositoryInterface;
use App\Domain\Shared\Exception\AbstractDomainException;
use App\Infrastructure\Compliance\Voter\RevokeDerAcknowledgementVoter;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'GenerateDERComponent',
    template: 'components/Compliance/GenerateDERComponent.html.twig',
)]
class GenerateDERComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: false)]
    public ?ComplianceFolder $complianceFolder = null;

    #[LiveProp]
    public bool $isGenerating = false;

    #[LiveProp(writable: true)]
    public string $revokeReason = '';

    public function __construct(
        private readonly AddDocumentUseCase $addDocumentUseCase,
        private readonly GenerateDerUseCase $generateDerUseCase,
        private readonly ComplianceDocumentRepositoryInterface $documentRepository,
        private readonly RequestStack $stack,
        private readonly LoggerInterface $logger,
        private readonly ComplianceFolderShowAssembler $complianceFolderShowAssembler,
        private readonly RevokeDerAcknowledgementUseCase $revokeDerAcknowledgementUseCase,
        private readonly Security $security,
    ) {
    }

    /**
     * Le CGP administrateur révoque l'accusé de réception (motif obligatoire).
     */
    #[LiveAction]
    public function revokeAcknowledgement(): void
    {
        if (!$this->complianceFolder instanceof ComplianceFolder) {
            return;
        }

        $acknowledgement = $this->getAcknowledgement();

        /** @var FlashBagAwareSessionInterface $session */
        $session = $this->stack->getSession();

        if (!$acknowledgement instanceof DerAcknowledgement) {
            return;
        }

        if (!$this->security->isGranted(RevokeDerAcknowledgementVoter::REVOKE, $this->complianceFolder)) {
            $session->getFlashBag()->add('error', 'La révocation d\'un accusé de réception est réservée aux administrateurs du cabinet.');

            return;
        }

        try {
            ($this->revokeDerAcknowledgementUseCase)($acknowledgement->slugId, $this->revokeReason);
            $this->revokeReason = '';
        } catch (\DomainException $exception) {
            $session->getFlashBag()->add('error', $exception->getMessage());
        }
    }

    public function isStepDerVisible(): bool
    {
        // Guard clause : sécurité d'affichage
        if (!$this->complianceFolder instanceof ComplianceFolder) {
            return false;
        }

        return !empty($this->complianceFolder->email);
    }

    public function getComplianceDTO(): ?ComplianceFolderShowResponse
    {
        // Guard clause : on ne tente pas l'assemblage sur du vide
        if (!$this->complianceFolder instanceof ComplianceFolder) {
            return null;
        }

        return $this->complianceFolderShowAssembler->assemble($this->complianceFolder);
    }

    /**
     * 1. L'ACTION : Appelée UNIQUEMENT quand on clique sur le bouton.
     */
    #[LiveAction]
    public function generateDer(): void
    {
        // 🛡️ Guard absolu : Si le front appelle cette action sans dossier, on bloque.
        Assert::notNull($this->complianceFolder, 'Action impossible : le dossier est introuvable.');

        $this->isGenerating = true;

        if (!$this->isProfileValid()) {
            $this->isGenerating = false;

            /** @var FlashBagAwareSessionInterface $session */
            $session = $this->stack->getSession();
            $session->getFlashBag()->add(
                type: 'error',
                message: 'Impossible de générer le DER : votre profil réglementaire est incomplet.'
            );

            return;
        }

        try {
            // 3. Récupération ou création du document
            $document = $this->documentRepository->findDerByFolder($this->complianceFolder);

            if (!$document instanceof \App\Domain\Compliance\Entity\ComplianceDocument) {
                $document = ($this->addDocumentUseCase)(DocumentType::DER, $this->complianceFolder);
            }

            Assert::notNull($document->id, 'L\'ID du document ne peut pas être nul.');

            // 4. Lancement de la génération (Asynchrone via Messenger)
            ($this->generateDerUseCase)(documentId: $document->id->toString(), folder: $this->complianceFolder);

            // Audit Trail
            $this->logger->info('Demande de génération de DER envoyée à Messenger avec succès', [
                'folder_id' => $this->complianceFolder->slugId,
                'document_id' => $document->id->toString(),
            ]);
        } catch (AbstractDomainException $e) {
            $this->isGenerating = false;

            $this->logger->error('Erreur métier lors de la génération du DER', [
                'folder_id' => $this->complianceFolder->slugId,
                'error' => $e->getMessage(),
            ]);

            /** @var FlashBagAwareSessionInterface $session */
            $session = $this->stack->getSession();
            $session->getFlashBag()->add(
                type: 'error',
                message: $e->getMessage(),
            );
        } catch (\Exception $e) {
            $this->isGenerating = false;

            $this->logger->critical('Crash système lors de la génération du DER', [
                'folder_id' => $this->complianceFolder->slugId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            /** @var FlashBagAwareSessionInterface $session */
            $session = $this->stack->getSession();
            $session->getFlashBag()->add(
                type: 'error',
                message: 'Une erreur inattendue est survenue lors de la génération.',
            );
        }
    }

    /**
     * 2. LA LECTURE : Appelée par Twig pour afficher (ou non) le bouton de téléchargement.
     */
    public function getDerPdfPath(): ?string
    {
        if (!$this->complianceFolder instanceof ComplianceFolder) {
            return null;
        }

        $document = $this->documentRepository->findDerByFolder($this->complianceFolder);

        return $document?->storagePath;
    }

    public function getDerDocument(): ?\App\Domain\Compliance\Entity\ComplianceDocument
    {
        if (!$this->complianceFolder instanceof ComplianceFolder) {
            return null;
        }

        return $this->documentRepository->findDerByFolder($this->complianceFolder);
    }

    /**
     * L'accusé de réception du DER en vigueur pour ce dossier, le cas échéant.
     */
    public function getAcknowledgement(): ?DerAcknowledgement
    {
        return $this->getDerDocument()?->acknowledgementInForce();
    }

    public function isDocumentReady(): bool
    {
        return null !== $this->getDerPdfPath();
    }

    public function isProfileValid(): bool
    {
        if (!$this->complianceFolder instanceof ComplianceFolder) {
            return false;
        }

        $profile = $this->complianceFolder->workspace->regulatoryProfile;

        if (!$profile instanceof \App\Domain\Firm\Entity\RegulatoryProfile) {
            return false;
        }

        // 🛡️ La forteresse réglementaire : TOUT doit être rempli
        return !in_array($profile->oriasNumber, [null, '', '0'], true)
            && !in_array($profile->professionalAssociation, [null, '', '0'], true)
            && !in_array($profile->rcProInsurer, [null, '', '0'], true)
            && !in_array($profile->rcProPolicyNumber, [null, '', '0'], true);
    }

    #[LiveAction]
    public function refresh(): void
    {
        // Mercure trigger
    }

    #[LiveListener('clientSaved')]
    public function onClientSaved(): void
    {
        // Force refresh
    }

    #[LiveListener('derStatusUpdated')]
    public function onDerStatusUpdated(): void
    {
        // Force refresh
    }
}
