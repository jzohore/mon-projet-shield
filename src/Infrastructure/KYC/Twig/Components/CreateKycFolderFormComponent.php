<?php

namespace App\Infrastructure\KYC\Twig\Components;

use App\Application\Kyc\DTO\Request\CreateKycFolderRequest;
use App\Application\Kyc\UseCase\CreateKycFolderUseCase;
use App\Application\Workspace\UseCase\GetCurrentWorkspaceInfo;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\KYC\Form\CreateKycFolderType;
use App\Infrastructure\Shared\Component\LiveFlashTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'CreateKycFolderFormComponent',
    template: 'components/Kyc/CreateKycFolderFormComponent.html.twig',
)]
class CreateKycFolderFormComponent
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use LiveFlashTrait;

    #[LiveProp]
    public ?string $userSlugId = null;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly LoggerInterface $logger,
        private readonly GetCurrentWorkspaceInfo $getCurrentWorkspaceInfo,
        private readonly UserRepositoryInterface $userRepository,
        private readonly CreateKycFolderUseCase $createKycFolderUseCase,
    ) {}

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(CreateKycFolderType::class, new CreateKycFolderRequest());
    }

    #[LiveAction]
    public function save(): void
    {
        $this->clearLiveFlash();
        $this->submitForm();

        /** @var CreateKycFolderRequest $dto */
        $dto = $this->getForm()->getData();
        try {

            $user = $this->userRepository->findBySlug($this->userSlugId);
            Assert::isInstanceOf($user, User::class);
            $workspace = ($this->getCurrentWorkspaceInfo)($user);
            $dto->workspaceSlugId = $workspace->slugId;
            ($this->createKycFolderUseCase)($dto);
            $this->resetForm();
            $this->addLiveFlash('success', 'La dossier à été initier  et l\'invitation a bien été envoyée.');
        } catch (\DomainException $e) {
            $this->logger->error('Erreur métier lors de la création du dossier KYC', [
                'contactEmail' => $dto->contactEmail,
                'contactFirstName' => $dto->contactFirstName,
                'error' => $e->getMessage(),
            ]);
            return;
        }
    }
}
