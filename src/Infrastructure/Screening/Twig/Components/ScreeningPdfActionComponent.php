<?php

namespace App\Infrastructure\Screening\Twig\Components;

use App\Application\Screening\UseCase\GenerateScreeningPdfUseCase;
use App\Application\Screening\UseCase\ShareDocumentUseCase;
use App\Domain\Screening\Entity\ScreeningAudit;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'ScreeningPdfActionComponent',
    template: 'components/Screening/ScreeningPdfActionComponent.html.twig'
)]
class ScreeningPdfActionComponent
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp]
    public ?ScreeningAudit $audit = null;

    /**
     * @var array<int, string>
     */
    #[LiveProp(writable: true)]
    public array $selectedEmails = [];

    #[LiveProp(writable: true)]
    public bool $isDocumentSent = false;

    public function __construct(
        private readonly GenerateScreeningPdfUseCase $generateScreeningPdfUseCase,
        private readonly WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private readonly ShareDocumentUseCase $shareDocumentUseCase,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @return array<int, mixed>
     */
    public function getMembers(): array
    {
        Assert::notNull($this->audit);
        Assert::notNull($this->audit->workspace->slugId);

        return $this->workspaceMemberRepository->getMembersActive($this->audit->workspace->slugId);
    }

    /**
     * @throws ExceptionInterface
     */
    #[LiveAction]
    public function generatePdf(): void
    {
        Assert::notNull($this->audit);
        Assert::notNull($this->audit->slugId);

        ($this->generateScreeningPdfUseCase)($this->audit);
    }

    #[LiveAction]
    public function sendDocument(): void
    {
        try {
            Assert::notNull($this->audit);
            Assert::notNull($this->audit->slugId);
            Assert::notNull($this->audit->owner->slugId);

            ($this->shareDocumentUseCase)($this->selectedEmails, $this->audit->slugId, $this->audit->owner->slugId);

            $this->isDocumentSent = true;
            $this->selectedEmails = [];
        } catch (\DomainException $e) {
            // Gérer l'erreur si besoin
        } catch (ExceptionInterface $e) {
            $this->logger->error('Erreur métier', ['error' => $e->getMessage()]);
        }
    }
}
