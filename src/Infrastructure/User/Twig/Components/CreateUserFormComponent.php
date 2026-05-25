<?php

namespace App\Infrastructure\User\Twig\Components;

use App\Application\User\DTO\Request\CreateUserRequest;
use App\Application\User\UseCase\CreateUserUseCase;
use App\Infrastructure\User\Form\CreateUserType;
use Psr\Log\LoggerInterface;
use Random\RandomException;
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

        } catch (\DomainException|RandomException $e) {
            $this->logger->error('Erreur métier lors de l\'inscription', [
                'email' => $userDTO->email,
                'error' => $e->getMessage(),
            ]);
            return; // On arrête tout, c'est une erreur de validation/métier

        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            $this->logger->info('Tentative d\'inscription avec un email existant', [
                'email' => $userDTO->email,
            ]);

        } catch (\Exception $e) {
            // 🚨 CAS CRITIQUE : Base de données HS, Serveur plein, etc.
            $this->logger->critical('Crash système lors de l\'inscription', [
                'error' => $e->getMessage(),
            ]);
            // Ici tu pourrais ajouter un message d'erreur générique pour l'UI
            $this->message = "Une erreur technique est survenue, veuillez réessayer plus tard.";
            return;
        }

        $this->isSuccessful = true;
        $this->message = 'Si cette adresse est valide, un lien vous a été envoyé.';
    }
}
