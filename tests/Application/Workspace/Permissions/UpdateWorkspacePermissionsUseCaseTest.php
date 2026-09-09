<?php

declare(strict_types=1);

namespace App\Tests\Application\Workspace\Permissions;

use App\Application\Workspace\UseCase\Permissions\UpdateWorkspacePermissionsUseCase;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Enum\PermissionMode;
use App\Domain\Workspace\Event\WorkspacePermissionsUpdatedEvent;
use App\Domain\Workspace\Exception\NotWorkspaceAdminException;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class UpdateWorkspacePermissionsUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private Workspace $workspace;
    private WorkspaceRepositoryInterface&MockObject $workspaceRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;

    private function useCase(bool $isAdmin = true): UpdateWorkspacePermissionsUseCase
    {
        $this->workspace = $this->createEntityState(Workspace::class, ['name' => 'Cabinet', 'slugId' => 'wrk_1']);

        $workspaceProvider = $this->createStub(CurrentWorkspaceProvider::class);
        $workspaceProvider->method('getWorkspace')->willReturn($this->workspace);
        $userProvider = $this->createStub(CurrentUserProvider::class);
        $userProvider->method('getUser')->willReturn($this->createEntityState(User::class, ['firstName' => 'A', 'lastName' => 'B', 'email' => 'a@b.fr']));
        $memberRepo = $this->createStub(WorkspaceMemberRepositoryInterface::class);
        $memberRepo->method('isUserAdminOfWorkspace')->willReturn($isAdmin);

        $this->workspaceRepository = $this->createMock(WorkspaceRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        return new UpdateWorkspacePermissionsUseCase(
            $workspaceProvider,
            $userProvider,
            $memberRepo,
            $this->workspaceRepository,
            $this->eventDispatcher,
        );
    }

    public function testAppliesTheNewPermissionsAndTracesTheChange(): void
    {
        $useCase = $this->useCase();
        $this->workspaceRepository->expects($this->once())->method('save')->with($this->workspace);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(WorkspacePermissionsUpdatedEvent::class))
            ->willReturnArgument(0);

        $useCase(
            canInvite: true,
            canManagePortfolio: false,
            canEditCabinet: true,
            canArchiveFolder: false,
            validationMode: PermissionMode::SUBMISSION,
        );

        self::assertTrue($this->workspace->collabCanInvite);
        self::assertFalse($this->workspace->collabCanManagePortfolio);
        self::assertSame(PermissionMode::SUBMISSION, $this->workspace->validationMode);
    }

    public function testRejectsNonAdmin(): void
    {
        $useCase = $this->useCase(isAdmin: false);
        $this->workspaceRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(NotWorkspaceAdminException::class);
        $useCase(false, false, false, false, PermissionMode::DELEGATED);
    }
}
