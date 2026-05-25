<?php

namespace App\Tests\Application\Workspace;

use App\Application\Workspace\UseCase\WorkspaceMember\RevokeWorkspaceMemberAccessUseCase;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Event\WorkspaceMemberRevokedEvent;
use App\Domain\Workspace\Exception\CannotRevokeOwnerException;
use App\Domain\Workspace\Exception\MemberNotFoundException;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

class RevokeWorkspaceMemberAccessUseCaseTest extends TestCase
{
    private WorkspaceMemberRepositoryInterface|MockObject $memberRepo;
    private CurrentWorkspaceProvider|MockObject $workspaceProvider;
    private CurrentUserProvider|MockObject $userProvider;
    private EventDispatcherInterface|MockObject $dispatcher;
    private RevokeWorkspaceMemberAccessUseCase $useCase;

    protected function setUp(): void
    {
        $this->memberRepo = $this->createMock(WorkspaceMemberRepositoryInterface::class);
        $this->workspaceProvider = $this->createMock(CurrentWorkspaceProvider::class);
        $this->userProvider = $this->createMock(CurrentUserProvider::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->useCase = new RevokeWorkspaceMemberAccessUseCase(
            $this->memberRepo,
            $this->workspaceProvider,
            $this->userProvider,
            $this->dispatcher
        );
    }

    /**
     * 🪄 L'arme secrète : Instancie une vraie entité sans passer par son constructeur
     * et force l'injection des propriétés (même private(set) ou readonly).
     */
    private function createEntity(string $class, array $properties = []): object
    {
        $reflection = new \ReflectionClass($class);
        // On crée l'objet sans déclencher le __construct()
        $entity = $reflection->newInstanceWithoutConstructor();

        foreach ($properties as $name => $value) {
            $prop = new \ReflectionProperty($class, $name);
            $prop->setValue($entity, $value); // Écrit directement dans la propriété
        }

        return $entity;
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testSuccessfulRevocation(): void
    {
        $targetId = 'usr_123';

        $workspace = $this->createEntity(Workspace::class, ['id' => Uuid::v4()]);
        $actor = $this->createEntity(User::class, ['id' => Uuid::v4()]);

        // 🪄 LE FIX EST ICI : On force bien le isOwner à false !
        $targetUser = $this->createEntity(User::class, [
            'id'      => Uuid::v4(),
            'isOwner' => false, // <-- CRUCIAL pour que le Use Case le laisse passer !
        ]);


        $member = $this->createEntity(WorkspaceMember::class, ['user' => $targetUser]);

        // Configuration des mocks
        $this->workspaceProvider->method('getWorkspace')->willReturn($workspace);
        $this->userProvider->method('getUser')->willReturn($actor);
        $this->memberRepo->method('findOneByUserSlugAndWorkspace')->willReturn($member);

        // Assertions
        $this->memberRepo->expects($this->once())->method('delete')->with($member);
        $this->dispatcher->expects($this->once())->method('dispatch')->with($this->isInstanceOf(WorkspaceMemberRevokedEvent::class));

        ($this->useCase)($targetId);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testThrowsExceptionWhenMemberNotFound(): void
    {
        $this->expectException(MemberNotFoundException::class);

        $workspace = $this->createEntity(Workspace::class, ['id' => Uuid::v4()]);
        $actor = $this->createEntity(User::class, ['id' => Uuid::v4()]);

        $this->workspaceProvider->method('getWorkspace')->willReturn($workspace);
        $this->userProvider->method('getUser')->willReturn($actor);
        $this->memberRepo->method('findOneByUserSlugAndWorkspace')->willReturn(null);

        ($this->useCase)('unknown_slug');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCannotRevokeOwner(): void
    {
        $this->expectException(CannotRevokeOwnerException::class);

        $workspace = $this->createEntity(Workspace::class, ['id' => Uuid::v4()]);
        $actor = $this->createEntity(User::class, ['id' => Uuid::v4()]);

        // 🪄 isOwner est forcé à true ici !
        $owner = $this->createEntity(User::class, ['id' => Uuid::v4(), 'isOwner' => true]);
        $owner->enabledProfil();

        $member = $this->createEntity(WorkspaceMember::class, ['user' => $owner]);

        $this->workspaceProvider->method('getWorkspace')->willReturn($workspace);
        $this->userProvider->method('getUser')->willReturn($actor);
        $this->memberRepo->method('findOneByUserSlugAndWorkspace')->willReturn($member);

        ($this->useCase)('owner_slug');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCannotRevokeSelf(): void
    {
        $this->expectException(\DomainException::class);

        $workspace = $this->createEntity(Workspace::class, ['id' => Uuid::v4()]);

        $actorId = Uuid::v4(); // On garde le même ID !
        $actor = $this->createEntity(User::class, ['id' => $actorId]);

        $target = $this->createEntity(User::class, ['id' => $actorId, 'isOwner' => false]);
        $target->enabledProfil();

        $member = $this->createEntity(WorkspaceMember::class, ['user' => $target]);

        $this->workspaceProvider->method('getWorkspace')->willReturn($workspace);
        $this->userProvider->method('getUser')->willReturn($actor);
        $this->memberRepo->method('findOneByUserSlugAndWorkspace')->willReturn($member);

        ($this->useCase)('my_slug');
    }
}
