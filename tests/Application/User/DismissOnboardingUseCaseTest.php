<?php

namespace App\Tests\Application\User;

use App\Application\User\UseCase\Dashboard\DismissOnboardingUseCase;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DismissOnboardingUseCaseTest extends TestCase
{
    private UserRepositoryInterface|MockObject $userRepositoryMock;
    private CurrentUserProvider|MockObject $currentUserProviderMock;
    private DismissOnboardingUseCase $useCase;

    protected function setUp(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->currentUserProviderMock = $this->createMock(CurrentUserProvider::class);

        $this->useCase = new DismissOnboardingUseCase(
            $this->userRepositoryMock,
            $this->currentUserProviderMock
        );
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testItDismissesOnboardingSuccessfully(): void
    {
        $realUser = clone User::create('test@example.com', 'John', 'Doe');

        $this->currentUserProviderMock
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($realUser);

        $this->userRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($realUser);

        ($this->useCase)();

        $this->assertTrue($realUser->profile->isDismiss());
    }
}
