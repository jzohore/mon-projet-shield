<?php

namespace App\Infrastructure\KYC\Twig\Components;

use App\Application\Kyc\DTO\Request\UploadKycDocumentRequest;
use App\Application\Kyc\UseCase\GetCurrentKycFolderUseCase;
use App\Application\Kyc\UseCase\UploadKycDocumentUseCase;
use App\Domain\Kyc\Entity\Stakeholder;
use App\Domain\Kyc\Enum\DocumentType;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use App\Domain\Kyc\Enum\CompanyLegalCategory;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'KycDocumentUploaderComponent',
    template: 'components/Kyc/KycDocumentUploaderComponent.html.twig',
)]
class KycDocumentUploaderComponent
{
    use DefaultActionTrait;

    public function __construct(
        private readonly GetCurrentKycFolderUseCase $getCurrentKycFolderUseCase,
        private readonly UploadKycDocumentUseCase $uploadKycDocumentUseCase,
        private readonly LoggerInterface $logger,
    ) {}

    #[LiveProp]
    public string $folderSlugId;

    #[LiveProp(writable: true)]
    public ?string $replacingSlotId = null;

    #[LiveAction]
    public function toggleReplace(#[LiveArg] string $id): void
    {
        // Si on reclique, on annule, sinon on active
        $this->replacingSlotId = ($this->replacingSlotId === $id) ? null : $id;
    }

    /**
     * @return array<array{id: string, title: string, description: string, icon: string, isUploaded: bool, fileName: ?string}>
     */
    public function getExpectedDocumentSlots(): array
    {
        $folder = ($this->getCurrentKycFolderUseCase)($this->folderSlugId);
        $existingDocuments = $folder->documents;

        // Cette fonction retourne désormais un tableau avec les infos du doc s'il existe
        $getDocInfo = function (DocumentType $type, ?Stakeholder $stakeholder = null) use ($existingDocuments): array {
            foreach ($existingDocuments as $doc) {
                if ($doc->type === $type && $doc->stakeholder === $stakeholder) {
                    return [
                        'isUploaded' => $doc->storagePath !== null,
                        'fileName' => $doc->storagePath ? basename($doc->storagePath) : null,
                    ];
                }
            }
            return ['isUploaded' => false, 'fileName' => null];
        };

        $slots = [];
        Assert::notNull($folder->legalCategory);
        $categoryEnum = CompanyLegalCategory::tryFrom($folder->legalCategory);

        // --- 1. DOCUMENTS GÉNÉRAUX ---
        if ($categoryEnum?->requiresKbis()) {
            $info = $getDocInfo(DocumentType::KBIS);
            $slots[] = [
                'id' => 'kbis',
                'title' => DocumentType::KBIS->getLabel(),
                'description' => 'Extrait Kbis de moins de 3 mois.',
                'icon' => 'tabler:building',
                'isUploaded' => $info['isUploaded'],
                'fileName' => $info['fileName'],
            ];
        }

        if ($categoryEnum?->requiresStatutes()) {
            $info = $getDocInfo(DocumentType::ARTICLES_OF_ASSOC);
            $slots[] = [
                'id' => 'articles',
                'title' => DocumentType::ARTICLES_OF_ASSOC->getLabel(),
                'description' => 'Statuts complets et à jour.',
                'icon' => 'tabler:file-certificate',
                'isUploaded' => $info['isUploaded'],
                'fileName' => $info['fileName'],
            ];
        }

        // --- 2. DOCUMENTS PERSONNELS ---
        foreach ($folder->stakeholders as $person) {
            $info = $getDocInfo(DocumentType::ID_CARD, $person);
            $slots[] = [
                'id' => 'id_card_' . $person->slugId,
                'title' => sprintf('Pièce d\'identité de %s %s', $person->firstName, $person->lastName),
                'description' => DocumentType::ID_CARD->getLabel(),
                'icon' => 'tabler:id',
                'isUploaded' => $info['isUploaded'],
                'fileName' => $info['fileName'],
            ];
        }
        return $slots;
    }
    #[LiveAction]
    public function uploadDocument(#[LiveArg] string $id, Request $request): void
    {
        // 1. On attrape le fichier de la requête
        /** @var UploadedFile|null $file */
        $file = $request->files->get('document_' . $id);

        if (!$file) {
            return; // Pas de fichier, on ignore.
        }

        // 2. On prépare la valise (DTO)
        $dto = new UploadKycDocumentRequest();
        $dto->folderSlugId = $this->folderSlugId;
        $dto->slotId = $id;
        $dto->file = $file;
        // 3. On lance la machine
        try {
            ($this->uploadKycDocumentUseCase)($dto);
        } catch (\Exception $e) {
            $this->logger->error('Erreur Upload', ['msg' => $e->getMessage()]);
            // Gérer l'affichage de l'erreur si besoin
        }
        $this->replacingSlotId = null; // On repasse en mode "Reçu"
    }
}
