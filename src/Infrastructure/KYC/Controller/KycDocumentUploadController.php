<?php

declare(strict_types=1);

namespace App\Infrastructure\KYC\Controller;

use App\Application\Compliance\DTO\Request\UploadDocumentRequest;
use App\Application\Compliance\UseCase\ComplianceDocument\UploadDocumentUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class KycDocumentUploadController extends AbstractController
{
    public function __construct(
        private readonly UploadDocumentUseCase $uploadDocumentUseCase,
    ) {
    }

    #[Route(
        path: '/portal/kyc/document/{documentId}/{folderId}/upload',
        name: 'portal_kyc_document_upload',
        methods: ['POST']
    )]
    public function __invoke(string $documentId, string $folderId, Request $request): JsonResponse
    {
        $file = $request->files->get('document');

        if (!$file instanceof UploadedFile) {
            return new JsonResponse([
                'ok' => false,
                'message' => 'Veuillez sélectionner un fichier valide.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $dto = new UploadDocumentRequest();
        $dto->folderId = $folderId;
        $dto->documentId = $documentId;
        $dto->file = $file;

        try {
            ($this->uploadDocumentUseCase)($dto);

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
        } catch (\Throwable) {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'enregistrement du document.');

            return new JsonResponse([
                'ok' => false,
                'message' => 'Une erreur système est survenue lors de l\'enregistrement.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
