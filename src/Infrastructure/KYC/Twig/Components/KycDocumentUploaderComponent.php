<?php

namespace App\Infrastructure\KYC\Twig\Components;

use App\Application\Kyc\UseCase\GetCurrentKycFolderUseCase;
use App\Application\Kyc\UseCase\SubmitKycFolderUseCase;
use App\Domain\Kyc\Enum\CompanyLegalCategory;
use App\Domain\Kyc\Enum\DocumentType;
use App\Infrastructure\Shared\Component\LiveFlashTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'KycDocumentUploaderComponent',
    template: 'components/Kyc/KycDocumentUploaderComponent.html.twig',
)]
class KycDocumentUploaderComponent
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;
    use LiveFlashTrait;

    public function __construct(
        private readonly GetCurrentKycFolderUseCase $getCurrentKycFolderUseCase,
        private readonly SubmitKycFolderUseCase $submitKycFolderUseCase,
        private readonly UrlGeneratorInterface $router,
    ) {}

    #[LiveProp]
    public string $folderSlugId;

    #[LiveProp(writable: true)]
    public ?string $replacingSlotId = null;

    #[LiveProp(writable: true)]
    public bool $isCertified = false;

    /**
     * @var array<string, string>
     */
    #[LiveProp(writable: true)]
    public array $newlyUploadedFiles = [];

    /**
     * @var array<string, bool>
     */
    #[LiveProp(writable: true)]
    public array $uploadingSlots = [];

    /**
     * @var array<string, string>
     */
    #[LiveProp(writable: true)]
    public array $lastErrors = [];

    #[LiveAction]
    public function toggleReplace(#[LiveArg] string $id): void
    {
        $this->clearLiveFlash();
        $this->replacingSlotId = ($this->replacingSlotId === $id) ? null : $id;
    }

    /**
     * @return array<int, array{id: string, title: string, description: string, icon: string, isUploaded: bool, fileName: ?string, isUploading: bool, error: ?string}>
     */
    public function getExpectedDocumentSlots(): array
    {
        $folder = ($this->getCurrentKycFolderUseCase)($this->folderSlugId);
        $existingDocuments = $folder->documents ?? [];

        // Closure de vérification inchangée et robuste
        $getDocInfo = function (DocumentType $type, ?string $stakeholderSlug = null) use ($existingDocuments): array {
            foreach ($existingDocuments as $doc) {
                $docType = $doc['type'] ?? $doc['documentType'] ?? null;
                $docLabel = $doc['typeLabel'] ?? null;

                $typeMatches = (
                    $docType === $type->value
                    || $docType === $type->name
                    || $docLabel === $type->getLabel()
                    || $docLabel === $type->value
                );

                if ($typeMatches) {
                    $docStakeholder = $doc['stakeholderSlug'] ?? $doc['stakeholderId'] ?? null;
                    if ($docStakeholder === $stakeholderSlug) {
                        return [
                            'isUploaded' => !empty($doc['storagePath']) || !empty($doc['fileName']),
                            'fileName' => $doc['fileName'] ?? (isset($doc['storagePath']) ? basename($doc['storagePath']) : null),
                        ];
                    }
                }
            }

            return ['isUploaded' => false, 'fileName' => null];
        };

        $slots = [];
        Assert::notNull($folder->legalCategory);
        $categoryEnum = CompanyLegalCategory::tryFrom($folder->legalCategory);

        // ==========================================
        // --- 1. DOCUMENTS GÉNÉRAUX DE L'ENTREPRISE
        // ==========================================

        // 1.1 Extrait Kbis
        if ($categoryEnum?->requiresKbis()) {
            $slotId = 'kbis';
            $info = $getDocInfo(DocumentType::KBIS);

            $slots[] = [
                'id' => $slotId,
                'title' => DocumentType::KBIS->getLabel(),
                'description' => 'Extrait Kbis de moins de 3 mois.',
                'icon' => 'tabler:building',
                'isUploaded' => $info['isUploaded'] || array_key_exists($slotId, $this->newlyUploadedFiles),
                'fileName' => $this->newlyUploadedFiles[$slotId] ?? $info['fileName'],
                'isUploading' => array_key_exists($slotId, $this->uploadingSlots), // Corrigé ici
                'error' => $this->lastErrors[$slotId] ?? null,
            ];
        }

        // 1.2 Statuts de la société
        if ($categoryEnum?->requiresStatutes()) {
            $slotId = 'articles_of_assoc';
            $info = $getDocInfo(DocumentType::ARTICLES_OF_ASSOC);

            $slots[] = [
                'id' => $slotId,
                'title' => DocumentType::ARTICLES_OF_ASSOC->getLabel(),
                'description' => 'Statuts constitutifs à jour, datés et signés.',
                'icon' => 'tabler:file-certificate',
                'isUploaded' => $info['isUploaded'] || array_key_exists($slotId, $this->newlyUploadedFiles),
                'fileName' => $this->newlyUploadedFiles[$slotId] ?? $info['fileName'],
                'isUploading' => array_key_exists($slotId, $this->uploadingSlots), // Corrigé ici
                'error' => $this->lastErrors[$slotId] ?? null,
            ];
        }

        // 1.3 Registre des Bénéficiaires Effectifs (RBE)
        if ($categoryEnum?->requiresUboDeclaration()) {
            $slotId = 'rbe';
            $info = $getDocInfo(DocumentType::RBE);

            $slots[] = [
                'id' => $slotId,
                'title' => DocumentType::RBE->getLabel(),
                'description' => 'Document officiel de déclaration des bénéficiaires effectifs.',
                'icon' => 'tabler:users-group',
                'isUploaded' => $info['isUploaded'] || array_key_exists($slotId, $this->newlyUploadedFiles),
                'fileName' => $this->newlyUploadedFiles[$slotId] ?? $info['fileName'],
                'isUploading' => array_key_exists($slotId, $this->uploadingSlots), // Corrigé ici
                'error' => $this->lastErrors[$slotId] ?? null,
            ];
        }

        // ==========================================
        // --- 2. DOCUMENTS PERSONNELS (INTERVENANTS)
        // ==========================================

        foreach ($folder->stakeholders as $person) {
            $slotId = 'id_card_' . $person['slugId'];
            $info = $getDocInfo(DocumentType::ID_CARD, $person['slugId']);

            $slots[] = [
                'id' => $slotId,
                'title' => sprintf('Pièce d\'identité (%s)', $person['fullName']),
                'description' => DocumentType::ID_CARD->getLabel(),
                'icon' => 'tabler:id',
                'isUploaded' => $info['isUploaded'] || array_key_exists($slotId, $this->newlyUploadedFiles),
                'fileName' => $this->newlyUploadedFiles[$slotId] ?? $info['fileName'],
                'isUploading' => array_key_exists($slotId, $this->uploadingSlots), // Corrigé ici
                'error' => $this->lastErrors[$slotId] ?? null,
            ];
        }

        return $slots;
    }
    #[LiveAction]
    public function uploaded(#[LiveArg] string $id, #[LiveArg] string $fileName): void
    {
        $this->clearLiveFlash();
        $this->newlyUploadedFiles[$id] = $fileName;
        $this->replacingSlotId = null;
        unset($this->uploadingSlots[$id], $this->lastErrors[$id]);

        $this->addLiveFlash('success', sprintf('Le document "%s" a bien été uploadé.', $fileName));
    }

    #[LiveAction]
    public function uploadError(#[LiveArg] string $id, #[LiveArg] string $message): void
    {
        $this->clearLiveFlash();
        $this->lastErrors[$id] = $message;
        unset($this->uploadingSlots[$id]);

        $this->addLiveFlash('error', $message);
    }

    #[LiveAction]
    public function submitFolder(): ?RedirectResponse
    {
        $this->validate();

        if (!$this->isCertified) {
            return null;
        }

        ($this->submitKycFolderUseCase)($this->folderSlugId, $this->isCertified);

        return new RedirectResponse($this->router->generate('portal_kyc_completed'));
    }
}
