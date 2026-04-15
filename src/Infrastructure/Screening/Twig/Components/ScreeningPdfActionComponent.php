<?php

namespace App\Infrastructure\Screening\Twig\Components;

use App\Application\Screening\DTO\Response\ScreeningResponse;
use App\Application\Screening\UseCase\GenerateScreeningPdfUseCase;
use App\Application\Screening\UseCase\GetScreeningInfo;
use App\Application\Screening\UseCase\ShareDocumentUseCase;
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
    public ?string $screeningId = null;

    #[LiveProp]
    public ?string $workSpaceId = null;

    /**
     * @var array<int, string>
     */
    #[LiveProp(writable: true)]
    public array $selectedEmails = [];

    #[LiveProp(writable: true)]
    public bool $isDocumentSent = false;

    public function __construct(
        private readonly GenerateScreeningPdfUseCase $generateScreeningPdfUseCase,
        private readonly GetScreeningInfo $getScreeningInfo,
        private readonly WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private readonly ShareDocumentUseCase $shareDocumentUseCase,
        private readonly LoggerInterface $logger,
    ) {}

    public function getAudit(): ScreeningResponse
    {
        Assert::notNull($this->screeningId);

        return ($this->getScreeningInfo)($this->screeningId);
    }

    /**
     * @return array<int, mixed>
     */
    public function getMembers(): array
    {
        $audit = $this->getAudit();
        Assert::notNull($audit->workspaceSlugId);

        return $this->workspaceMemberRepository->getMembersActive($audit->workspaceSlugId);
    }

    /**
     * @throws ExceptionInterface
     */
    #[LiveAction]
    public function generatePdf(): void
    {
        Assert::notNull($this->screeningId);

        ($this->generateScreeningPdfUseCase)($this->screeningId);
    }

    #[LiveAction]
    public function sendDocument(): void
    {
        try {
            $audit = $this->getAudit();

            Assert::notNull($audit->slugId);
            Assert::notNull($audit->userSlugId);

            ($this->shareDocumentUseCase)($this->selectedEmails, $audit->slugId, $audit->userSlugId);

            $this->isDocumentSent = true;
            $this->selectedEmails = [];
        } catch (\DomainException $e) {
            // Gérer l'erreur si besoin
        } catch (ExceptionInterface $e) {
            $this->logger->error('Erreur métier', ['error' => $e->getMessage()]);
        }
    }
}
