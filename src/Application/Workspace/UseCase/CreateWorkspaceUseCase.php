<?php

declare(strict_types=1);

namespace App\Application\Workspace\UseCase;

use App\Application\Workspace\DTO\Request\CreateWorkspaceRequest;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Event\WorkspaceCreatedEvent;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

final readonly class CreateWorkspaceUseCase
{
    public function __construct(
        private WorkspaceRepositoryInterface $workspaceRepository,
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(CreateWorkspaceRequest $request): void
    {
        Assert::notNull($request->name);
        Assert::notNull($request->userSlugId, 'Le slug utilisateur est requis.');
        Assert::notNull($request->siret, 'Le numéro SIRET est requis.');
        Assert::notNull($request->address, 'L\'adresse est requise.');
        Assert::notNull($request->legalName, 'Le nom juridique est requis.');
        $user = $this->userRepository->findBySlug($request->userSlugId);

        Assert::isInstanceOf($user, User::class, sprintf('Aucun utilisateur trouvé pour le slug "%s"', $request->userSlugId));

        $workspace = Workspace::create(
            name: $request->name,
            siret: $request->siret,
            legalName: $request->legalName,
            address: $request->address,
        );
        $user->onboardingStatus = OnboardingStatus::WORKSPACE_SETUP;
        $this->workspaceRepository->save($workspace);
        $this->userRepository->save($user);

        $this->eventDispatcher->dispatch(new WorkspaceCreatedEvent($workspace, $user));
    }
}
