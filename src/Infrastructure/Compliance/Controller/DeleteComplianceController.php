<?php

namespace App\Infrastructure\Compliance\Controller;

use App\Application\Compliance\UseCase\DeleteComplianceFolderUseCase;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Exception\CannotDeleteActiveFolderException;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[AsController]
#[Route(path: '/app/compliance/folder/{slugId}/delete', name: 'app_compliance_delete_folder', methods: ['POST'])]
#[IsCsrfTokenValid('remove-compliance')]
class DeleteComplianceController extends AbstractController
{
    public function __construct(
        private DeleteComplianceFolderUseCase $deleteComplianceFolderUseCase,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(
        #[MapEntity(mapping: ['slugId' => 'slugId'])]
        ComplianceFolder $complianceFolder,
    ): RedirectResponse {
        try {
            // 2. Exécution du UseCase
            ($this->deleteComplianceFolderUseCase)($complianceFolder);

            // 3. Feedback positif pour l'UX
            $this->addFlash('success', 'Le brouillon a été supprimé avec succès.');

        } catch (CannotDeleteActiveFolderException $exception) {
            // 4. On gère NOTRE exception métier (l'utilisateur a fait une bêtise)
            $this->addFlash('error', $exception->getMessage());
            $this->logger->warning('Tentative de suppression d\'un dossier actif', ['id' => $complianceFolder->id]);

        } catch (\Exception $exception) {
            // 5. On gère les vraies erreurs serveur (ex: BDD down)
            $this->addFlash('error', 'Une erreur technique est survenue lors de la suppression.');
            $this->logger->error('Erreur lors de la suppression du dossier KYC', [
                'exception' => $exception->getMessage(),
            ]);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_kyc_list'));
    }
}
