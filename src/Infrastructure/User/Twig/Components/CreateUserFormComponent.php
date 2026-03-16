<?php

namespace App\Infrastructure\User\Twig\Components;

use App\Application\User\DTO\Request\CreateUserRequest;
use App\Application\User\UseCase\CreateUserUseCase;
use App\Infrastructure\User\Form\CreateUserType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'CreateUserFormComponent',
    template: 'components/User/CreateUserFormComponent.html.twig',
)]
final class CreateUserFormComponent
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public bool $isSuccessful = false;

    #[LiveProp]
    public ?string $message = null;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly CreateUserUseCase $createUser,
        private readonly LoggerInterface $logger,
    ) {}

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(CreateUserType::class, new CreateUserRequest());
    }

    #[LiveAction]
    public function save(): void
    {
        $this->submitForm();

        /** @var CreateUserRequest $userDTO */
        $userDTO = $this->getForm()->getData();

        try {
            ($this->createUser)($userDTO);

        } catch (\DomainException $e) {
            $this->logger->error('Erreur métier lors de l\'inscription', [
                'email' => $userDTO->email,
                'error' => $e->getMessage(),
            ]);
            return;
        }

        $this->isSuccessful = true;


        $this->message = 'Si cette adresse est valide, un lien vous a été envoyé.';
    }
}
