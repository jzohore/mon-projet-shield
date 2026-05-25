<?php

namespace App\Tests\Application\User;

use App\Application\User\UseCase\UpdateOnboardingStatusUseCase;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateOnboardingStatusUseCaseTest extends TestCase
{
    private CurrentUserProvider|MockObject $currentUserProviderMock;
    private UserRepositoryInterface|MockObject $userRepositoryMock;
    private UpdateOnboardingStatusUseCase $useCase;

    protected function setUp(): void
    {
        // On mocke les deux services
        $this->currentUserProviderMock = $this->createMock(CurrentUserProvider::class);
        $this->userRepositoryMock = $this->createMock(UserRepositoryInterface::class);

        // On instancie le UseCase
        $this->useCase = new UpdateOnboardingStatusUseCase(
            $this->currentUserProviderMock,
            $this->userRepositoryMock
        );
    }

    public function testItUpdatesOnboardingStatusSuccessfully(): void
    {
        // --- ARRANGE ---
        // On prépare le statut qu'on veut injecter (j'imagine que 'COMPLETED' existe dans ton Enum)
        $newStatus = OnboardingStatus::COMPLETED;

        // 🪄 On instancie un VRAI utilisateur
        $realUser = User::create('test@example.com', 'John', 'Doe');

        // (Optionnel) On s'assure que le statut de départ est différent pour que le test soit pertinent
        // $realUser->onboardingStatus = OnboardingStatus::PENDING;

        // On dit au Provider de retourner notre vrai utilisateur
        $this->currentUserProviderMock
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($realUser);

        // On s'attend à ce que le repository sauvegarde ce même utilisateur
        $this->userRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($realUser);

        // --- ACT ---
        ($this->useCase)($newStatus);

        // --- ASSERT ---
        // On vérifie que la propriété interne de l'utilisateur a bien été écrasée par le nouveau statut
        $this->assertSame($newStatus, $realUser->onboardingStatus);
    }
}
