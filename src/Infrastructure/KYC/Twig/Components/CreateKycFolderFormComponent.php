<?php

declare(strict_types=1);

namespace App\Infrastructure\KYC\Twig\Components;

use App\Application\Kyc\DTO\Request\CreateKycFolderRequest;
use App\Application\Kyc\UseCase\CreateKycFolderUseCase;
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

#[AsLiveComponent(
    name: 'CreateKycFolderFormComponent',
    template: 'components/Kyc/CreateKycFolderFormComponent.html.twig',
)]
class CreateKycFolderFormComponent
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;
    use LiveFlashTrait;

    #[LiveProp]
    public string $workspaceSlugId = '';

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly LoggerInterface $logger,
        private readonly CreateKycFolderUseCase $createKycFolderUseCase,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        $dto = new CreateKycFolderRequest();
        $dto->workspaceSlugId = $this->workspaceSlugId;

        return $this->formFactory->create(CreateKycFolderType::class, $dto);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->clearLiveFlash();
        $this->submitForm();

        /** @var CreateKycFolderRequest $dto */
        $dto = $this->getForm()->getData();
        try {
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
