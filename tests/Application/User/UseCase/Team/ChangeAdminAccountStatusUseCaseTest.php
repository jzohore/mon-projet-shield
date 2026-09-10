<?php

declare(strict_types=1);

namespace App\Tests\Application\User\UseCase\Team;

use App\Application\User\UseCase\Team\ChangeAdminAccountStatusUseCase;
use App\Domain\User\Entity\Admin;
use App\Domain\User\Enum\AdminAccountAction;
use App\Domain\User\Enum\AdminRole;
use App\Domain\User\Enum\AdminStatusChange;
use App\Domain\User\Event\AdminAccountActionOccurred;
use App\Domain\User\Repository\AdminRepositoryInterface;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ChangeAdminAccountStatusUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private AdminRepositoryInterface&MockObject $adminRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private ChangeAdminAccountStatusUseCase $useCase;

    protected function setUp(): void
    {
        $this->adminRepository = $this->createMock(AdminRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->useCase = new ChangeAdminAccountStatusUseCase($this->adminRepository, $this->eventDispatcher);
    }

    private function admin(string $email, array $roles, bool $isActif): Admin
    {
        return $this->createEntityState(Admin::class, [
            'slugId' => 'adm_test',
            'email' => $email,
            'firstName' => 'Sam',
            'lastName' => 'Ops',
            'roles' => $roles,
            'isActif' => $isActif,
        ]);
    }

    public function testItSuspendsAnAccountAndDispatchesTheEvent(): void
    {
        $this->adminRepository->method('findBySlugId')
            ->willReturn($this->admin('sam@kysure.fr', [AdminRole::OPERATOR->value], true));
        $this->adminRepository->method('countActiveSuperAdmins')->willReturn(3);
        $this->adminRepository->expects($this->once())->method('save');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (AdminAccountActionOccurred $event): bool => AdminAccountAction::SUSPENDED === $event->action))
            ->willReturnArgument(0);

        ($this->useCase)('adm_x', AdminStatusChange::SUSPEND, 'boss@kysure.fr', 'La Boss');
    }

    public function testAnOperatorCannotChangeItsOwnStatus(): void
    {
        $this->adminRepository->method('findBySlugId')
            ->willReturn($this->admin('boss@kysure.fr', [AdminRole::SUPER_ADMIN->value], true));
        $this->adminRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);

        ($this->useCase)('adm_x', AdminStatusChange::SUSPEND, 'BOSS@kysure.fr', 'La Boss');
    }

    public function testTheLastActiveSuperAdminCannotBeSuspended(): void
    {
        $this->adminRepository->method('findBySlugId')
            ->willReturn($this->admin('solo@kysure.fr', [AdminRole::SUPER_ADMIN->value], true));
        $this->adminRepository->method('countActiveSuperAdmins')->willReturn(1);
        $this->adminRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);

        ($this->useCase)('adm_x', AdminStatusChange::SUSPEND, 'boss@kysure.fr', 'La Boss');
    }

    public function testSuspendingAnAlreadySuspendedAccountIsANoop(): void
    {
        $suspended = $this->admin('sam@kysure.fr', [AdminRole::OPERATOR->value], false);
        $suspended->suspend();

        $this->adminRepository->method('findBySlugId')->willReturn($suspended);
        $this->adminRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        ($this->useCase)('adm_x', AdminStatusChange::SUSPEND, 'boss@kysure.fr', 'La Boss');
    }
}
