<?php

namespace App\Infrastructure\Compliance\Twig\Components;

use App\Application\Compliance\DTO\Request\SetIndividualClientRequest;
use App\Application\Compliance\UseCase\SetIndividualClientUseCase;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Entity\IndividualFolder;
use App\Domain\Shared\Exception\AbstractDomainException;
use App\Infrastructure\Compliance\Form\SetIndividualClientType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'ManualIndividualStepOneComponent',
    template: 'components/Compliance/ManualIndividualStepOneComponent.html.twig',
)]
class ManualIndividualStepOneComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp(writable: false)]
    public ComplianceFolder $complianceFolder;

    #[LiveProp(writable: false)]
    public string $method;

    #[LiveProp(writable: false)]
    public string $type;

    public function __construct(
        private readonly SetIndividualClientUseCase $setClientUseCase,
        private readonly LoggerInterface $logger
    ) {}

    protected function instantiateForm(): FormInterface
    {
        // 1. On initialise le DTO
        $request = new SetIndividualClientRequest();

        // Note: Assure-toi que ton DTO utilise bien 'reference' ou 'folderSlugId' selon ton UseCase
        $request->reference = $this->complianceFolder->reference;

        // 2. 🪄 Le pré-remplissage
        // On s'assure d'abord que le dossier est bien un dossier physique pour éviter un crash
        if ($this->complianceFolder instanceof IndividualFolder) {
            $request->firstName = $this->complianceFolder->firstName ?? '';
            $request->lastName  = $this->complianceFolder->lastName ?? '';
            $request->email     = $this->complianceFolder->email ?? '';
        }

        // 3. On crée le formulaire avec le DTO pré-rempli
        return $this->createForm(SetIndividualClientType::class, $request);
    }
    #[LiveAction]
    public function save(): ?RedirectResponse
    {
        // On soumet le formulaire et on valide les contraintes du DTO
        $this->submitForm();

        /** @var SetIndividualClientRequest $request */
        $request = $this->getForm()->getData();

        try {
            // Exécution de la logique métier
            ($this->setClientUseCase)($request);

            // Redirection vers l'étape 2 (ex: Pièces d'identité)
            $this->addFlash('success', 'Les informations ont bien été enregistrées');
            return $this->redirectToRoute('app_compliance_method_new', [
                'type' => $this->type,
                'method' => $this->method,
                'slugId' => $this->complianceFolder->slugId,
            ]);

        } catch (AbstractDomainException $e) {
            $this->logger->error('Erreur lors de la sauvegarde du client (Étape 1)', [
                'slugId' => $this->complianceFolder->slugId,
                'error' => $e->getMessage(),
            ]);

            $this->addFlash('error', $e);

            return null;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la sauvegarde du client (Étape 1)', [
                'slugId' => $this->complianceFolder->slugId,
                'error' => $e->getMessage(),
            ]);

            $this->addFlash('error', 'Une erreur technique est survenue lors de l\'enregistrement.');

            return null;
        }
    }
}
