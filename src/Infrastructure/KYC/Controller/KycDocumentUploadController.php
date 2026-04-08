<?php

namespace App\Infrastructure\KYC\Controller;

use App\Application\Kyc\DTO\Request\UploadKycDocumentRequest;
use App\Application\Kyc\UseCase\UploadKycDocumentUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[AsController]
final class KycDocumentUploadController extends AbstractController
{
    public function __construct(
        private readonly UploadKycDocumentUseCase $uploadKycDocumentUseCase,
    ) {}

    #[Route(
        path: '/portal/kyc/document/{id}/upload',
        name: 'portal_kyc_document_upload',
        methods: ['POST']
    )]
    public function __invoke(string $id, Request $request): Response
    {
        $folderSlugId = (string) $request->request->get('folderSlugId', '');
        $file = $request->files->get('document');

        if (!$file instanceof UploadedFile) {
            return new JsonResponse([
                'ok' => false,
                'message' => 'Veuillez sélectionner un fichier valide.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $dto = new UploadKycDocumentRequest();
        $dto->folderSlugId = $folderSlugId;
        $dto->slotId = $id;
        $dto->file = $file;

        try {
            ($this->uploadKycDocumentUseCase)($dto);

            return new JsonResponse([
                'ok' => true,
                'fileName' => $file->getClientOriginalName(),
            ]);
        } catch (\DomainException|\InvalidArgumentException $e) {
            // 2. C'est une règle métier qui a pété (ex: "Ce dossier KYC est déjà clôturé")
            return new JsonResponse([
                'ok' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'ok' => false,
                'message' => 'Une erreur système est survenue lors de l\'enregistrement.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
