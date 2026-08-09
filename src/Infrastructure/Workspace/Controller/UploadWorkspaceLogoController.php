<?php

declare(strict_types=1);

namespace App\Infrastructure\Workspace\Controller;

use App\Application\Firm\DTO\Request\UploadLogoRequest;
use App\Application\Firm\UseCase\UploadWorkspaceLogoUseCase;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webmozart\Assert\Assert;

#[Route(
    path: '/api/workspaces/current/logo/upload',
    name: 'api_workspace_logo_upload',
    methods: ['POST']
)]
class UploadWorkspaceLogoController extends AbstractController
{
    public function __construct(
        private readonly UploadWorkspaceLogoUseCase $uploadUseCase,
        private readonly CurrentWorkspaceProvider $workspaceProvider,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        // 1. Récupération du fichier depuis le FormData (clé 'logo')
        $file = $request->files->get('logo');

        if (!$file instanceof UploadedFile) {
            return new JsonResponse([
                'ok' => false,
                'message' => 'Veuillez sélectionner un fichier image valide.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // 2. Hydratation du DTO
        $dto = new UploadLogoRequest();
        $dto->logoFile = $file;

        // 3. Validation manuelle du DTO (Poids, Format)
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            return new JsonResponse([
                'ok' => false,
                'message' => $violations[0]?->getMessage(), // Renvoie l'erreur de l'Assert
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            // 4. Exécution du Use Case (Envoi sur S3)
            ($this->uploadUseCase)($dto);

            // 5. On récupère le nouveau chemin pour que le Front puisse afficher l'image
            $workspace = $this->workspaceProvider->getWorkspace();
            Assert::notNull($workspace->regulatoryProfile);

            return new JsonResponse([
                'ok' => true,
                'fileName' => $file->getClientOriginalName(),
                'newLogoPath' => $workspace->regulatoryProfile->filename,
                'redirectUrl' => $this->generateUrl('app_settings_regulatory_profile'),
            ]);
        } catch (\DomainException|\InvalidArgumentException $e) {
            return new JsonResponse([
                'ok' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable) {
            return new JsonResponse([
                'ok' => false,
                'message' => 'Une erreur système est survenue lors de l\'enregistrement du logo.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
