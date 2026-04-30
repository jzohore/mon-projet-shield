<?php

namespace App\Infrastructure\Workspace\Twig\Components;

use App\Application\Workspace\UseCase\GetCurrentWorkspaceInfo;
use App\Application\Workspace\UseCase\Onboarding\BindWorkspaceTypeUseCase;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use App\Domain\Workspace\Enum\WorkspaceType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'WorkspaceChoosePlanFormComponent',
    template: 'components/Workspace/WorkspaceChoosePlanFormComponent.html.twig',
)]
class WorkspaceChoosePlanFormComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $workspaceType = 'individual';

    #[LiveProp]
    public ?string $userSlugId = null;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly GetCurrentWorkspaceInfo $currentWorkspaceInfo,
        private readonly BindWorkspaceTypeUseCase $bindWorkspaceTypeUseCase,
        private readonly UrlGeneratorInterface $generator,
    ) {}

    #[LiveAction]
    public function finish(): Response
    {
        // 1. Sécurisation : On vérifie que la valeur correspond bien à notre Enum
        $type = WorkspaceType::tryFrom($this->workspaceType);

        if (!$type) {
            throw new \InvalidArgumentException('Type de structure invalide.');
        }

        Assert::notNull($this->userSlugId);
        $user = $this->userRepository->getBySlug($this->userSlugId);
        Assert::notNull($user->id);
        Assert::notNull($user->slugId);
        $workspace = ($this->currentWorkspaceInfo)($user->id);
        Assert::notNull($workspace->slugId);
        ($this->bindWorkspaceTypeUseCase)($type, $workspace->slugId, $user->slugId);

        return new RedirectResponse($this->generator->generate('app_onboarding_finalization'));
    }
}
