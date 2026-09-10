<?php

declare(strict_types=1);

namespace App\Tests\Application\User\UseCase\Team;

use App\Application\User\DTO\Request\UpdateAdminAccountInput;
use App\Application\User\UseCase\Team\UpdateAdminAccountUseCase;
use App\Domain\User\Entity\Admin;
use App\Domain\User\Enum\AdminAccountAction;
use App\Domain\User\Enum\AdminRole;
use App\Domain\User\Event\AdminAccountActionOccurred;
use App\Domain\User\Repository\AdminRepositoryInterface;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class UpdateAdminAccountUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private AdminRepositoryInterface&MockObject $adminRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private UpdateAdminAccountUseCase $useCase;

    protected function setUp(): void
    {
        $this->adminRepository = $this->createMock(AdminRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->useCase = new UpdateAdminAccountUseCase($this->adminRepository, $this->eventDispatcher);
    }

    private function admin(array $roles): Admin
    {
        return $this->createEntityState(Admin::class, [
            'slugId' => 'adm_test',
            'email' => 'sam@kysure.fr',
            'firstName' => 'Sam',
            'lastName' => 'Ops',
            'phoneNumber' => null,
            'roles' => $roles,
            'isActif' => true,
        ]);
    }

    public function testARoleChangeDispatchesRolesChanged(): void
    {
        $this->adminRepository->method('findBySlugId')->willReturn($this->admin([AdminRole::OPERATOR->value]));
        $this->adminRepository->method('countActiveSuperAdmins')->willReturn(2);
        $this->adminRepository->expects($this->once())->method('save');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (AdminAccountActionOccurred $event): bool => AdminAccountAction::ROLES_CHANGED === $event->action))
            ->willReturnArgument(0);

        ($this->useCase)(
            new UpdateAdminAccountInput('adm_x', 'Sam', 'Ops', null, AdminRole::SUPER_ADMIN->value),
            'boss@kysure.fr',
            'La Boss',
        );
    }

    public function testAProfileOnlyChangeDispatchesProfileUpdated(): void
    {
        $this->adminRepository->method('findBySlugId')->willReturn($this->admin([AdminRole::OPERATOR->value]));
        $this->adminRepository->expects($this->once())->method('save');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (AdminAccountActionOccurred $event): bool => AdminAccountAction::PROFILE_UPDATED === $event->action))
            ->willReturnArgument(0);

        ($this->useCase)(
            new UpdateAdminAccountInput('adm_x', 'Samuel', 'Ops', null, AdminRole::OPERATOR->value),
            'boss@kysure.fr',
            'La Boss',
        );
    }

    public function testItRefusesToDowngradeTheLastActiveSuperAdmin(): void
    {
        $this->adminRepository->method('findBySlugId')->willReturn($this->admin([AdminRole::SUPER_ADMIN->value]));
        $this->adminRepository->method('countActiveSuperAdmins')->willReturn(1);
        $this->adminRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);

        ($this->useCase)(
            new UpdateAdminAccountInput('adm_x', 'Sam', 'Ops', null, AdminRole::OPERATOR->value),
            'boss@kysure.fr',
            'La Boss',
        );
    }

    public function testNoChangeDispatchesNothing(): void
    {
        $this->adminRepository->method('findBySlugId')->willReturn($this->admin([AdminRole::OPERATOR->value]));
        $this->adminRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        ($this->useCase)(
            new UpdateAdminAccountInput('adm_x', 'Sam', 'Ops', null, AdminRole::OPERATOR->value),
            'boss@kysure.fr',
            'La Boss',
        );
    }
}
