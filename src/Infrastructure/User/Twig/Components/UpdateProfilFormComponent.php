<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Twig\Components;

use App\Application\User\DTO\Request\UserProfilRequest;
use App\Infrastructure\User\Form\UpdateProfilType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'UpdateProfilFormComponent',
    template: 'components/User/UpdateProfilFormComponent.html.twig',
)]
class UpdateProfilFormComponent
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public ?string $userSlugId = null;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly LoggerInterface $logger,
        private readonly UrlGeneratorInterface $router,
        private readonly RequestStack $requestStack,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(UpdateProfilType::class, new UserProfilRequest());
    }

    #[LiveAction]
    public function save(): ?RedirectResponse
    {
        $this->submitForm();

        /** @var UserProfilRequest $dto */
        $dto = $this->getForm()->getData();

        try {
            $dto->userSlugId = $this->userSlugId;
            $request = $this->requestStack->getCurrentRequest();
            Assert::notNull($request, 'Cette action doit être exécutée dans un contexte HTTP.');

            $dto->lang = $request->getLocale();
            // ($this->updateProfilUseCase)($dto);
        } catch (\DomainException $e) {
            $this->logger->error('Erreur métier lors de la mise à jour du profil', [
                'user_slug_id' => $this->userSlugId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        return new RedirectResponse($this->router->generate('app_onboarding_finalization'));
    }
}
