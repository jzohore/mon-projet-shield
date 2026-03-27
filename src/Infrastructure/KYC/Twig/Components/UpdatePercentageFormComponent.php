<?php

namespace App\Infrastructure\KYC\Twig\Components;

use App\Application\Kyc\UseCase\UpdatePercentageUseCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(
    name: 'UpdatePercentageFormComponent',
    template: 'components/Kyc/UpdatePercentageFormComponent.html.twig',
)]
class UpdatePercentageFormComponent
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp]
    public ?string $folderSlugId = null;

    #[LiveProp]
    public ?string $stakeHoldersId = null;

    #[LiveProp]
    public bool $isEditing = false;

    #[LiveProp(writable: true)]
    #[Assert\NotBlank]
    #[Assert\GreaterThanOrEqual(0)]
    #[Assert\LessThanOrEqual(100)]
    public ?int $percentage = null;

    public function __construct(
        private readonly UpdatePercentageUseCase $updatePercentageUseCase,
        private readonly LoggerInterface $logger,
    ) {}

    #[LiveAction]
    public function activateEditing(): void
    {
        $this->isEditing = true;
    }

    #[LiveAction]
    public function updatePercentage(): void
    {
        $this->validate();
        if (!$this->isValid()) {
            return;
        }
        \Webmozart\Assert\Assert::notNull($this->stakeHoldersId);
        \Webmozart\Assert\Assert::notNull($this->percentage);
        try {
            ($this->updatePercentageUseCase)($this->stakeHoldersId, $this->percentage);
            $this->isEditing = false;
        } catch (\DomainException $e) {
            $this->logger->error('Erreur métier lors de la modification du pourcentage', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
