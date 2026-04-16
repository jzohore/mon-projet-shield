<?php

declare(strict_types=1);

namespace App\Tests\Application\User\UseCase;

use App\Application\User\DTO\Request\CreateUserRequest;
use App\Application\User\UseCase\CreateUserUseCase;
use App\Domain\User\Entity\User;
use App\Domain\User\Event\UserCreatedEvent;
use App\Domain\User\Repository\UserRepositoryInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class CreateUserUseCaseTest extends TestCase
{
    #[Test]
    public function it_creates_a_user_when_it_does_not_exist_yet(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $request = new CreateUserRequest();
        $request->email = 'john.doe@example.com';
        $request->firstName = 'John';
        $request->lastName = 'Doe';
        $request->isAdmin = false;

        $userRepository->expects(self::once())
            ->method('findByEmail')
            ->with($request->email)
            ->willReturn(null);

        $userRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(static function (User $user): bool {
                return $user->email === 'john.doe@example.com'
                    && $user->firstName === 'John'
                    && $user->lastName === 'Doe'
                    && in_array('ROLE_USER', $user->roles, true)
                    && null !== $user->magicLinkToken
                    && null !== $user->magicLinkTokenExpiresAt;
            }));

        $eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (object $event): bool {
                return $event instanceof UserCreatedEvent;
            }));

        $useCase = new CreateUserUseCase($userRepository, $eventDispatcher);

        $useCase($request);
    }

    #[Test]
    public function it_does_nothing_when_the_user_already_exists(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $request = new CreateUserRequest();
        $request->email = 'john.doe@example.com';
        $request->firstName = 'John';
        $request->lastName = 'Doe';
        $request->isAdmin = false;

        $existingUser = User::create(
            email: 'john.doe@example.com',
            firstName: 'John',
            lastName: 'Doe',
        );

        $userRepository->expects(self::once())
            ->method('findByEmail')
            ->with($request->email)
            ->willReturn($existingUser);

        $userRepository->expects(self::never())
            ->method('save');

        $eventDispatcher->expects(self::never())
            ->method('dispatch');

        $useCase = new CreateUserUseCase($userRepository, $eventDispatcher);

        $useCase($request);
    }

    #[Test]
    public function it_promotes_the_user_to_admin_when_requested(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $request = new CreateUserRequest();
        $request->email = 'admin@example.com';
        $request->firstName = 'Admin';
        $request->lastName = 'User';
        $request->isAdmin = true;

        $userRepository->expects(self::once())
            ->method('findByEmail')
            ->with($request->email)
            ->willReturn(null);

        $userRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(static function (User $user): bool {
                return in_array('ROLE_ADMIN', $user->roles, true);
            }));

        $eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (object $event): bool {
                return $event instanceof UserCreatedEvent;
            }));

        $useCase = new CreateUserUseCase($userRepository, $eventDispatcher);

        $useCase($request);
    }
}
