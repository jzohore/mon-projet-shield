<?php

declare(strict_types=1);

namespace App\Tests\Application\User;

use App\Application\User\UseCase\UpdateStripeCustomerIdUseCase;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\UserProfil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdateStripeCustomerIdUseCaseTest extends TestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private UpdateStripeCustomerIdUseCase $useCase;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->useCase = new UpdateStripeCustomerIdUseCase($this->userRepository);
    }

    /**
     * 🛡️ Helper DDD : Création d'un User "coquille" avec un Profil mocké
     * Cela permet d'espionner (Spy) les méthodes appelées sur le profil interne.
     */
    private function createUserWithMockedProfile(MockObject $mockedProfile): User
    {
        $reflection = new \ReflectionClass(User::class);
        /** @var User $user */
        $user = $reflection->newInstanceWithoutConstructor();

        // On injecte le profil mocké dans la propriété du User
        $profileProperty = $reflection->getProperty('profile');
        $profileProperty->setValue($user, $mockedProfile);

        return $user;
    }

    public function testSuccessfullyUpdatesStripeCustomerIdAndSavesUser(): void
    {
        // Arrange
        $stripeCustomerId = 'cus_123456789KYSURE';

        // On mocke le profil pour vérifier qu'il reçoit bien l'ordre de mise à jour
        $mockedProfile = $this->createMock(UserProfil::class);

        $mockedProfile->expects($this->once())
            ->method('updateStripeCustomerId')
            ->with($stripeCustomerId);

        $user = $this->createUserWithMockedProfile($mockedProfile);

        // Assert : On vérifie que le repository sauvegarde bien l'agrégat racine (User)
        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($user);

        // Act
        ($this->useCase)($user, $stripeCustomerId);
    }
}
