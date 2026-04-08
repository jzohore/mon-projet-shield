<?php

namespace App\Infrastructure\User\Twig\Components;

use App\Application\User\DTO\Request\UpdateNameRequest;
use App\Application\User\UseCase\UpdateNameUseCase;
use App\Infrastructure\Shared\Component\LiveFlashTrait;
use App\Infrastructure\User\Form\UpdateNameType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'UpdateNameFormComponent',
    template: 'components/User/UpdateNameFormComponent.html.twig',
)]
class UpdateNameFormComponent
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use LiveFlashTrait;

    #[LiveProp]
    public ?string $userSlugId = null;

    #[LiveProp]
    public ?string $firstName = null;

    #[LiveProp]
    public ?string $lastName = null;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly UpdateNameUseCase $updateNameUseCase,
        private readonly LoggerInterface $logger,
    ) {}

    protected function instantiateForm(): FormInterface
    {
        $dto = new UpdateNameRequest();

        $dto->firstName = $this->firstName;
        $dto->lastName = $this->lastName;
        return $this->formFactory->create(UpdateNameType::class, $dto);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->submitForm();

        if (!$this->getForm()->isValid()) {
            return; // LiveComponent gérera l'affichage des erreurs automatiquement
        }

        /** @var UpdateNameRequest $userDTO */
        $userDTO = $this->getForm()->getData();

        try {
            $userDTO->userSlugId = $this->userSlugId;
            ($this->updateNameUseCase)($userDTO);
            $this->addLiveFlash('success', 'Vos informations ont bien été modifié.');

        } catch (\DomainException $e) {
            $this->logger->error('Erreur métier lors de la mise à jour du nom et prénom', [
                'email' => $userDTO->userSlugId,
                'error' => $e->getMessage(),
            ]);
            return;
        }

    }
}
