<?php

declare(strict_types=1);

namespace App\Tests\Application\User\Register;

use App\Application\User\UseCase\MarkAsOnboardingCompletedUseCase;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class MarkAsOnboardingCompletedUseCaseTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private MarkAsOnboardingCompletedUseCase $useCase;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->useCase = new MarkAsOnboardingCompletedUseCase($this->userRepository);
    }

    /**
     * 🛡️ Helper DDD : Instanciation d'un User dans un état initial précis.
     */
    private function createUserInPendingState(): User
    {
        $reflection = new \ReflectionClass(User::class);
        /** @var User $user */
        $user = $reflection->newInstanceWithoutConstructor();

        // On suppose que l'état initial avant de terminer l'onboarding est PENDING ou WORKSPACE_SETUP
        $reflection->getProperty('onboardingStatus')->setValue($user, OnboardingStatus::PENDING);

        return $user;
    }

    public function testSuccessfullyMarksUserAsOnboardingCompleted(): void
    {
        // Arrange
        $user = $this->createUserInPendingState();

        // Assert
        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(static fn (User $savedUser): bool => OnboardingStatus::COMPLETED === $savedUser->onboardingStatus));

        // Act
        ($this->useCase)($user);
    }
}
