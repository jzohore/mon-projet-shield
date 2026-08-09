<?php

declare(strict_types=1);

namespace App\Infrastructure\Firm\Twig;

use App\Application\Firm\DTO\Request\PartnerDTO;
use App\Application\Firm\DTO\Request\UpdateRegulatoryProfileRequest;
use App\Application\Firm\UseCase\UpdateRegulatoryProfileUseCase;
use App\Domain\Firm\Repository\RegulatoryProfileRepositoryInterface;
use App\Infrastructure\Firm\Form\RegulatoryProfileType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'RegulatoryProfileFormComponent',
    template: 'components/Firm/RegulatoryProfileFormComponent.html.twig',
)]
class RegulatoryProfileFormComponent
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public ?string $id = null;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly LoggerInterface $logger,
        private readonly UrlGeneratorInterface $router,
        private readonly RequestStack $requestStack,
        private readonly RegulatoryProfileRepositoryInterface $regulatoryProfileRepository,
        private readonly UpdateRegulatoryProfileUseCase $updateUseCase,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        Assert::notNull($this->id);
        $profile = $this->regulatoryProfileRepository->findById($this->id);
        Assert::notNull($profile);
        $dto = new UpdateRegulatoryProfileRequest();

        $dto->isIndependent = $profile->isIndependent;
        $dto->oriasNumber = $profile->oriasNumber;
        $dto->professionalAssociation = $profile->professionalAssociation;
        $dto->rcProInsurer = $profile->rcProInsurer;
        $dto->rcProPolicyNumber = $profile->rcProPolicyNumber;
        $dto->partners = [];
        foreach ($profile->partners as $partnerData) {
            $partnerDto = new PartnerDTO();
            $partnerDto->name = $partnerData['name'];
            $partnerDto->email = $partnerData['email'];
            $partnerDto->address = $partnerData['address'];
            $partnerDto->phone = $partnerData['phone'];

            $dto->partners[] = $partnerDto;
        }

        return $this->formFactory->create(RegulatoryProfileType::class, $dto);
    }

    /**
     * Action appelée via AJAX quand on clique sur "Ajouter un partenaire".
     */
    #[LiveAction]
    public function addPartner(): void
    {
        // On modifie l'état brut du formulaire soumis.
        // Symfony UX se chargera de re-rendre le composant avec un nouveau champ vide.
        $this->formValues['partners'][] = [];
    }

    /**
     * Action appelée via AJAX quand on clique sur "Supprimer".
     */
    #[LiveAction]
    public function removePartner(#[LiveArg] int $index): void
    {
        unset($this->formValues['partners'][$index]);
    }

    #[LiveAction]
    public function save(): ?RedirectResponse
    {
        $this->submitForm();

        /** @var UpdateRegulatoryProfileRequest $dto */
        $dto = $this->getForm()->getData();

        try {
            ($this->updateUseCase)($dto);

            /** @var FlashBagAwareSessionInterface $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add(
                type: 'success',
                message: 'Votre profil réglementaire a bien été mis à jour.'
            );

            return new RedirectResponse($this->router->generate('app_settings_regulatory_profile'));
        } catch (\DomainException $e) {
            $this->logger->error('Erreur métier lors de l\'édition du profil réglementaire', [
                'orias' => $dto->oriasNumber,
                'error' => $e->getMessage(),
            ]);

            return null;
        } catch (\Exception $e) {
            $this->logger->critical('Crash système lors de l\'édition du profil', [
                'error' => $e->getMessage(),
            ]);

            /** @var FlashBagAwareSessionInterface $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add(
                type: 'error',
                message: 'Une erreur technique est survenue lors de l\'enregistrement. Veuillez réessayer.'
            );

            return new RedirectResponse($this->router->generate('app_settings_regulatory_profile'));
        }
    }
}
