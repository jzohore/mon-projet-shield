<?php

namespace App\Infrastructure\KYC\Twig\Components;

use App\Application\Kyc\DTO\Request\AddStakeholderRequest;
use App\Application\Kyc\UseCase\RemoveStakeHolderUseCase;
use App\Application\Kyc\UseCase\SaveNewStakeHolderUseCase;
use App\Domain\Kyc\Entity\KycFolder;
use App\Domain\Kyc\Entity\Stakeholder;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use App\Infrastructure\KYC\Form\AddStakeholderType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'ManageStakeholdersComponent',
    template: 'components/Kyc/ManageStakeholdersComponent.html.twig',
)]
class ManageStakeholdersComponent
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?string $folderSlugId = null;

    #[LiveProp(writable: true)]
    public bool $isAddingNew = false;

    // Ajoute cette méthode pour ouvrir/fermer
    #[LiveAction]
    public function toggleAddForm(): void
    {
        $this->isAddingNew = !$this->isAddingNew;
        $this->resetForm(); // On vide le formulaire si on l'ouvre ou l'annule
    }

    public function __construct(
        private readonly KycFolderRepositoryInterface $folderRepository,
        private EntityManagerInterface $entityManager,
        private readonly FormFactoryInterface $formFactory,
        private readonly SaveNewStakeHolderUseCase $saveNewStakeHolderUseCase,
        private readonly LoggerInterface $logger,
        private readonly RemoveStakeHolderUseCase $removeStakeHolderUseCase,
    ) {}

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(AddStakeholderType::class, new AddStakeholderRequest());
    }

    public function getFolder(): KycFolder
    {
        $folderSlugId = $this->folderSlugId;
        Assert::notNull($folderSlugId);

        $folder = $this->folderRepository->findBySlugId($folderSlugId);
        Assert::notNull($folder);

        return $folder;
    }

    #[LiveAction]
    public function saveNewStakeholder(): void
    {
        $this->submitForm();

        if (!$this->getForm()->isValid()) {
            return;
        }

        /** @var AddStakeholderRequest $dtoStakeholder */
        $dtoStakeholder = $this->getForm()->getData();
        $dtoStakeholder->folderSlugId = $this->folderSlugId;

        try {
            ($this->saveNewStakeHolderUseCase)($dtoStakeholder);
            $this->resetForm();
        } catch (\DomainException $e) {
            $this->logger->error('Erreur métier lors de l\'ajout d\'un dirigeant au dossier KYC', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    #[LiveAction]
    public function updatePercentage(#[LiveArg] string $id, #[LiveArg] ?float $inlinePercentage): void
    {
        if ($inlinePercentage === null) {
            return;
        }

        $stakeholder = $this->entityManager->getRepository(Stakeholder::class)->find($id);

        if ($stakeholder && $stakeholder->folder === $this->getFolder()) {
            //$stakeholder->updateOwnershipPercentage($inlinePercentage);
            $this->entityManager->flush();
        }
    }

    #[LiveAction]
    public function removeStakeholder(#[LiveArg] string $id): void
    {
        ($this->removeStakeHolderUseCase)($id);
    }
}
