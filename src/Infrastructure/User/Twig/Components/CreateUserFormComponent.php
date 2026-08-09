<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Twig\Components;

use App\Application\User\DTO\Request\CreateUserRequest;
use App\Application\User\DTO\Request\LoginUserRequest;
use App\Application\User\UseCase\Register\CreateUserUseCase;
use App\Application\User\UseCase\SendLoginUserUseCase;
use App\Domain\User\Exception\UserAlreadyExistsException;
use App\Infrastructure\User\Form\CreateUserType;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
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
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public bool $isSuccessful = false;

    #[LiveProp]
    public ?string $message = null;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly CreateUserUseCase $createUser,
        private readonly LoggerInterface $logger,
        private readonly SendLoginUserUseCase $sendLoginUser,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(CreateUserType::class);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->submitForm();
        $form = $this->getForm();

        if (!$form->isValid()) {
            return;
        }

        /** @var CreateUserRequest $userDTO */
        $userDTO = $form->getData();

        try {
            ($this->createUser)($userDTO);
        } catch (UserAlreadyExistsException) {
            $this->logger->info('Tentative d\'inscription sur un compte existant (User Enumeration Defense).', [
                'email' => $userDTO->email,
            ]);
            $this->sendLoginUser(
                $userDTO->email,
            );
        } catch (\DomainException $e) {
            $this->logger->error('Erreur métier lors de l\'inscription', [
                'email' => $userDTO->email,
                'error' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            $this->logger->critical('Crash système lors de l\'inscription', [
                'error' => $e->getMessage(),
            ]);
            $this->message = 'Une erreur technique est survenue, veuillez réessayer plus tard.';

            return;
        }

        $this->isSuccessful = true;
        $this->message = 'Si cette adresse est valide, un lien vous a été envoyé.';
    }

    /**
     * @throws RandomException
     * @throws ExceptionInterface
     */
    private function sendLoginUser(string $email): void
    {
        $request = new LoginUserRequest(
            email: $email,
        );

        ($this->sendLoginUser)($request);
    }
}
