<?php

namespace App\Infrastructure\Screening\Twig\Components;

use App\Application\Screening\DTO\Request\ScreeningRequest;
use App\Application\Screening\DTO\Response\ScreeningResponse;
use App\Application\Screening\UseCase\PerformScreeningUseCase;
use App\Application\Workspace\UseCase\GetCurrentWorkspaceInfo;
use App\Infrastructure\Screening\Form\ScreeningType;
use App\Infrastructure\Shared\Component\LiveFlashTrait;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'ScreeningSearchComponent',
    template: 'components/Screening/ScreeningSearchComponent.html.twig'
)]
class ScreeningSearchComponent
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use LiveFlashTrait;

    #[LiveProp]
    public ?ScreeningResponse $results = null;

    #[LiveProp]
    public bool $isSearching = false;

    #[LiveProp]
    public Uuid $userSlugId;

    #[LiveProp]
    public string $userEmail;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly PerformScreeningUseCase $performScreeningUseCase,
        private readonly GetCurrentWorkspaceInfo $getCurrentWorkspaceInfo,
        private readonly UrlGeneratorInterface $router,
    ) {}

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(ScreeningType::class, new ScreeningRequest());
    }

    #[LiveAction]
    public function performSearch(): ?RedirectResponse
    {
        $this->submitForm();

        /** @var ScreeningRequest $dto */
        $dto = $this->getForm()->getData();

        $workspace = ($this->getCurrentWorkspaceInfo)($this->userSlugId);
        Assert::notNull($workspace);
        Assert::notNull($workspace->slugId);

        $dto->workspaceSlugId = $workspace->slugId;
        $dto->chargeCredit = true;
        $dto->userEmail = $this->userEmail;

        $this->isSearching = true;

        try {
            $this->results = ($this->performScreeningUseCase)($dto);
            Assert::notNull($this->results);
            $slugId = $this->results->slugId;
            Assert::notNull($slugId);
            $this->resetForm();

            return new RedirectResponse(
                $this->router->generate('app_screening_show', ['slugId' => $slugId])
            );
        } catch (\Exception $e) {
            $this->addLiveFlash('error', 'Le rapport n\'a pas pu être envoyé aux destinataires.');
        } catch (\Throwable $e) {
            $this->addLiveFlash('error', 'Le rapport n\'a pas pu être envoyé aux destinataires.');
        } finally {
            $this->isSearching = false;
        }

        return new RedirectResponse($this->router->generate('app_screening_new'));
    }
}
