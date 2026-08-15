<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Twig\Components;

use App\Application\User\DTO\Request\UserProfilRequest;
use App\Application\User\UseCase\UpdateUserInformationUseCase;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Infrastructure\Shared\Component\LiveFlashTrait;
use App\Infrastructure\User\Form\UpdateProfilType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(
    name: 'UpdateNameFormComponent',
    template: 'components/User/UpdateNameFormComponent.html.twig',
)]
class UpdateNameFormComponent extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;
    use LiveFlashTrait;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly UpdateUserInformationUseCase $updateNameUseCase,
        private readonly LoggerInterface $logger,
        private readonly CurrentUserProvider $currentUserProvider,
        private readonly CurrentWorkspaceProvider $currentWorkspaceProvider,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        $user = $this->currentUserProvider->getUser();
        $dto = new UserProfilRequest();

        $dto->firstName = $user->firstName;
        $dto->lastName = $user->lastName;
        $dto->jobTitle = $user->profile->jobTitle;
        $dto->phoneNumber = $user->profile->phoneNumber;

        return $this->formFactory->create(UpdateProfilType::class, $dto);
    }

    #[LiveAction]
    public function save(): RedirectResponse
    {
        $this->submitForm();

        /** @var UserProfilRequest $userDTO */
        $userDTO = $this->getForm()->getData();

        try {
            $user = $this->currentUserProvider->getUser();
            $workspace = $this->currentWorkspaceProvider->getWorkspace();
            ($this->updateNameUseCase)(
                request: $userDTO,
                user: $user,
                workspace: $workspace,
            );

            $this->addFlash(
                type: 'success',
                message: 'Vos informations ont bien été modifié.'
            );
        } catch (\DomainException $e) {
            $this->logger->error('Erreur métier lors de la mise à jour du nom et prénom', [
                'error' => $e->getMessage(),
            ]);

            $this->addFlash(
                type: 'error',
                message: 'Une erreur est survenue lors de la modification de vos informations. Veuillez réessayer.'
            );
        }

        return $this->redirectToRoute('app_settings_account');
    }
}
