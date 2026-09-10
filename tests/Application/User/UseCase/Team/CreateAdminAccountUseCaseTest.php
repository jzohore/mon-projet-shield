<?php

declare(strict_types=1);

namespace App\Tests\Application\User\UseCase\Team;

use App\Application\User\DTO\Request\CreateAdminAccountInput;
use App\Application\User\UseCase\Team\CreateAdminAccountUseCase;
use App\Domain\User\Entity\Admin;
use App\Domain\User\Enum\AdminAccountAction;
use App\Domain\User\Enum\AdminRole;
use App\Domain\User\Event\AdminAccountActionOccurred;
use App\Domain\User\Repository\AdminRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class CreateAdminAccountUseCaseTest extends TestCase
{
    private AdminRepositoryInterface&MockObject $adminRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private CreateAdminAccountUseCase $useCase;

    protected function setUp(): void
    {
        $this->adminRepository = $this->createMock(AdminRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->useCase = new CreateAdminAccountUseCase($this->adminRepository, $this->eventDispatcher);
    }

    public function testItCreatesAnActiveAccountAndDispatchesTheCreatedEvent(): void
    {
        $this->adminRepository->method('findByEmail')->willReturn(null);
        $this->adminRepository->expects($this->once())->method('save');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static function (AdminAccountActionOccurred $event): bool {
                self::assertSame(AdminAccountAction::CREATED, $event->action);
                self::assertSame('nina.ops@kysure.fr', $event->adminEmail);
                self::assertSame('operateur@kysure.fr', $event->initiatorEmail);

                return true;
            }))
            ->willReturnArgument(0);

        $admin = ($this->useCase)(
            new CreateAdminAccountInput('  Nina.Ops@kysure.fr ', 'Nina', 'Ops', null, AdminRole::OPERATOR->value),
            'operateur@kysure.fr',
            'Opérateur KYSURE',
        );

        self::assertInstanceOf(Admin::class, $admin);
        self::assertSame('nina.ops@kysure.fr', $admin->email);
        self::assertTrue($admin->isActif);
        self::assertSame([AdminRole::OPERATOR->value], $admin->getRoles());
    }

    public function testItRejectsAnAlreadyUsedEmail(): void
    {
        $this->adminRepository->method('findByEmail')->willReturn($this->createStub(Admin::class));
        $this->adminRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\InvalidArgumentException::class);

        ($this->useCase)(
            new CreateAdminAccountInput('taken@kysure.fr', 'Taken', 'Account', null, AdminRole::SUPER_ADMIN->value),
            'operateur@kysure.fr',
            'Opérateur KYSURE',
        );
    }

    public function testItRejectsAnUnknownRole(): void
    {
        $this->adminRepository->method('findByEmail')->willReturn(null);
        $this->adminRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\InvalidArgumentException::class);

        ($this->useCase)(
            new CreateAdminAccountInput('new@kysure.fr', 'New', 'Account', null, 'ROLE_WHATEVER'),
            'operateur@kysure.fr',
            'Opérateur KYSURE',
        );
    }
}
