<?php

namespace App\Tests\Application\User;

use App\Application\User\DTO\Request\CreateUserRequest;
use App\Application\User\DTO\Response\UserInfoResponse;
use App\Application\User\UseCase\CreateUserUseCase;
use App\Domain\User\Entity\User;
use App\Domain\User\Event\UserCreatedEvent;
use App\Domain\User\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CreateUserUseCaseTest extends TestCase
{
    private UserRepositoryInterface|MockObject $userRepositoryMock;
    private EventDispatcherInterface|MockObject $eventDispatcherMock;
    private CreateUserUseCase $useCase;

    protected function setUp(): void
    {
        // 1. Initialisation des Mocks (les "doublures" de nos interfaces)
        $this->userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);

        // 2. Instanciation du UseCase avec les Mocks
        $this->useCase = new CreateUserUseCase(
            $this->userRepositoryMock,
            $this->eventDispatcherMock
        );
    }

    public function testItCreatesAStandardUserSuccessfully(): void
    {
        // --- ARRANGE (Préparation) ---
        $request = new CreateUserRequest();
        $request->email = 'john.doe@example.com';
        $request->firstName = 'John';
        $request->lastName = 'Doe';

        // On s'attend à ce que la méthode save() soit appelée exactement 1 fois.
        // On vérifie que l'objet passé à save() est bien un User avec le bon email.
        $this->userRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $user) use ($request) {
                // Assertions internes au mock
                $this->assertSame($request->email, $user->email); // Remplacer par ton getter
                $this->assertNotNull($user->magicLinkToken); // Vérifie que le token a bien été généré

                return true;
            }));

        // On s'attend à ce que l'événement soit dispatché exactement 1 fois
        $this->eventDispatcherMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(UserCreatedEvent::class));

        // --- ACT (Exécution) ---
        $response = ($this->useCase)($request);

        // --- ASSERT (Vérification de la réponse) ---
        $this->assertInstanceOf(UserInfoResponse::class, $response);
        // Tu peux ajouter des assertions sur ton DTO de réponse ici
        $this->assertSame('john.doe@example.com', $response->email);
    }
}
