<?php

namespace App\Tests\Application\User;

use App\Application\User\DTO\Request\UserProfilRequest;
use App\Application\User\UseCase\UpdateUserInformationUseCase;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateUserInformationUseCaseTest extends TestCase
{
    private UserRepositoryInterface|MockObject $userRepositoryMock;
    private CurrentUserProvider|MockObject $currentUserProviderMock; // Mocke l'interface, pas la classe !
    private UpdateUserInformationUseCase $useCase;

    protected function setUp(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepositoryInterface::class);

        // Note: C'est ici que l'interface CurrentUserProviderInterface brille !
        $this->currentUserProviderMock = $this->createMock(CurrentUserProvider::class);

        $this->useCase = new UpdateUserInformationUseCase(
            $this->userRepositoryMock,
            $this->currentUserProviderMock
        );
    }

    public function testItUpdatesUserNameSuccessfully(): void
    {
        // --- ARRANGE ---
        $request = new UserProfilRequest();
        $request->firstName = 'Jane';
        $request->lastName = 'Smith';

        $realUser = clone User::create('test@example.com', 'John', 'Doe');

        $this->currentUserProviderMock
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($realUser);

        $this->userRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($realUser);

        // --- ACT ---
        ($this->useCase)($request);

        $this->assertSame('Jane', $realUser->firstName);
        $this->assertSame('Smith', $realUser->lastName);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testItThrowsExceptionIfFirstNameIsMissing(): void
    {
        // --- ARRANGE ---
        $request = new UserProfilRequest();
        $request->firstName = null; // L'erreur déclencheuse
        $request->lastName = 'Smith';

        // On dit à PHPUnit qu'on S'ATTEND à ce qu'une exception explose
        $this->expectException(\InvalidArgumentException::class);

        ($this->useCase)($request);
    }
}
