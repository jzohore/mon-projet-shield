<?php

declare(strict_types=1);

namespace App\Tests\Application\User\Register;

use App\Application\User\DTO\Request\CreateUserRequest;
use App\Application\User\UseCase\Register\CreateUserUseCase;
use App\Domain\User\Entity\User;
use App\Domain\User\Event\UserRegisteredEvent;
use App\Domain\User\Repository\UserRepositoryInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class CreateUserUseCaseTest extends TestCase
{
    private MockObject $userRepositoryMock;
    private MockObject $eventDispatcherMock;

    private CreateUserUseCase $useCase;

    protected function setUp(): void
    {
        // 1. Initialisation des Mocks (les "doublures" de nos interfaces)
        $this->userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        // 2. Instanciation du UseCase avec les Mocks
        $this->useCase = new CreateUserUseCase(
            $this->userRepositoryMock,
            $this->eventDispatcherMock,
            $logger
        );
    }

    /**
     * @throws RandomException
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testItCreatesAStandardUserSuccessfully(): void
    {
        // --- ARRANGE ---
        $request = new CreateUserRequest(
            email: 'john.doe@example.com',
            firstName: 'John',
            lastName: 'Doe'
        );

        $this->userRepositoryMock
            ->expects($this->once())
            ->method('existsByEmail')
            ->with('john.doe@example.com')
            ->willReturn(false);

        $this->userRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $user) use ($request): bool {
                $this->assertSame($request->email, $user->getNormalizedEmail());
                $this->assertSame('John DOE', $user->getFullName());
                $this->assertNotNull($user->magicLinkToken);

                return true;
            }));

        $this->eventDispatcherMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (UserRegisteredEvent $event) use ($request): bool {
                $this->assertSame($request->email, $event->email);

                return true;
            }));

        ($this->useCase)($request);
    }
}
