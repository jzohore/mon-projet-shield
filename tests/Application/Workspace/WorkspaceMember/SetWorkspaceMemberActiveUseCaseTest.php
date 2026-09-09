<?php

declare(strict_types=1);

namespace App\Tests\Application\Workspace\WorkspaceMember;

use App\Application\Workspace\UseCase\WorkspaceMember\SetWorkspaceMemberActiveUseCase;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Event\WorkspaceMemberSuspendedEvent;
use App\Domain\Workspace\Exception\NotWorkspaceAdminException;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Domain\Workspace\Service\WorkspacePermissionChecker;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

#[AllowMockObjectsWithoutExpectations]
final class SetWorkspaceMemberActiveUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private UserRepositoryInterface&MockObject $userRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private User $target;

    private function useCase(bool $isAdmin = true, bool $targetActive = true, bool $targetOwner = false): SetWorkspaceMemberActiveUseCase
    {
        $workspace = $this->createEntityState(Workspace::class, ['name' => 'Cabinet', 'slugId' => 'wrk_1', 'id' => Uuid::v7()]);
        $this->target = $this->createEntityState(User::class, [
            'id' => Uuid::v7(), 'slugId' => 'usr_t', 'email' => 't@c.fr', 'firstName' => 'T', 'lastName' => 'T',
            'isActif' => $targetActive, 'isOwner' => $targetOwner, 'securityStamp' => 'stamp',
        ]);
        $member = $this->createEntityState(WorkspaceMember::class, ['user' => $this->target]);

        $workspaceProvider = $this->createStub(CurrentWorkspaceProvider::class);
        $workspaceProvider->method('getWorkspace')->willReturn($workspace);
        $userProvider = $this->createStub(CurrentUserProvider::class);
        $userProvider->method('getUser')->willReturn($this->createEntityState(User::class, ['id' => Uuid::v7(), 'email' => 'admin@c.fr', 'firstName' => 'A', 'lastName' => 'D']));

        $members = $this->createStub(WorkspaceMemberRepositoryInterface::class);
        $members->method('findOneByUserSlugAndWorkspace')->willReturn($member);
        $checker = $this->createStub(WorkspacePermissionChecker::class);
        $checker->method('isAdmin')->willReturn($isAdmin);

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        return new SetWorkspaceMemberActiveUseCase($members, $workspaceProvider, $userProvider, $checker, $this->userRepository, $this->eventDispatcher);
    }

    public function testSuspendsAnActiveMember(): void
    {
        $useCase = $this->useCase(targetActive: true);
        $this->userRepository->expects($this->once())->method('save')->with($this->target);
        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->with($this->isInstanceOf(WorkspaceMemberSuspendedEvent::class));

        $useCase('usr_t', false);

        self::assertFalse($this->target->isActif);
    }

    public function testIsIdempotentWhenAlreadyInTargetState(): void
    {
        $useCase = $this->useCase(targetActive: false);
        $this->userRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $useCase('usr_t', false);
    }

    public function testRejectsNonAdmin(): void
    {
        $useCase = $this->useCase(isAdmin: false);
        $this->userRepository->expects($this->never())->method('save');

        $this->expectException(NotWorkspaceAdminException::class);
        $useCase('usr_t', false);
    }
}
