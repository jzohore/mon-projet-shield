<?php

declare(strict_types=1);

namespace App\Tests\Application\Compliance\UseCase\Client;

use App\Application\Compliance\DTO\Request\CreateClientRequest;
use App\Application\Compliance\UseCase\Client\CreateOrAttachClientUseCase;
use App\Domain\Compliance\Event\ClientAddedToWorkspaceEvent;
use App\Domain\User\Entity\Client;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\ClientRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class CreateOrAttachClientUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private ClientRepositoryInterface&MockObject $clientRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private Workspace $workspace;
    private CreateOrAttachClientUseCase $useCase;

    protected function setUp(): void
    {
        $this->clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->workspace = $this->createEntityState(Workspace::class, [
            'slugId' => 'wrk_1',
            'name' => 'Cabinet Test',
            'clients' => new ArrayCollection(),
        ]);

        $workspaceProvider = $this->createStub(CurrentWorkspaceProvider::class);
        $workspaceProvider->method('getWorkspace')->willReturn($this->workspace);

        $userProvider = $this->createStub(CurrentUserProvider::class);
        $userProvider->method('getUser')->willReturn(
            $this->createEntityState(User::class, ['slugId' => 'usr_1', 'firstName' => 'Marie', 'lastName' => 'Curie'])
        );

        $this->useCase = new CreateOrAttachClientUseCase(
            $this->clientRepository,
            $workspaceProvider,
            $userProvider,
            $this->eventDispatcher,
        );
    }

    private function request(string $email = 'Jean.Dupont@Example.com'): CreateClientRequest
    {
        $request = new CreateClientRequest();
        $request->firstName = '  Jean ';
        $request->lastName = ' Dupont ';
        $request->email = $email;

        return $request;
    }

    public function testCreatesANewClientNormalisesEmailAndDispatchesCreatedEvent(): void
    {
        $this->clientRepository->expects($this->once())->method('findByEmail')
            ->with('jean.dupont@example.com')->willReturn(null);

        $this->clientRepository->expects($this->once())->method('save')
            ->with($this->isInstanceOf(Client::class));

        $captured = null;
        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$captured): object {
                $captured = $event;

                return $event;
            });

        $client = ($this->useCase)($this->request());

        self::assertSame('jean.dupont@example.com', $client->email);
        self::assertSame('Jean', $client->firstName);
        self::assertSame('Dupont', $client->lastName);
        self::assertTrue($client->isActif);
        self::assertTrue($this->workspace->clients->contains($client));

        self::assertInstanceOf(ClientAddedToWorkspaceEvent::class, $captured);
        self::assertTrue($captured->wasCreated);
        self::assertSame('wrk_1', $captured->workspaceSlugId);
    }

    public function testAttachesAnExistingClientWithoutOverwritingMasterDataAndFlagsNotCreated(): void
    {
        $existing = Client::initiate('jean.dupont@example.com', 'Jean-Baptiste', 'Dupond', isActif: true);
        $this->clientRepository->method('findByEmail')->willReturn($existing);

        $this->clientRepository->expects($this->once())->method('save')->with($existing);

        $captured = null;
        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$captured): object {
                $captured = $event;

                return $event;
            });

        $client = ($this->useCase)($this->request());

        self::assertSame('Jean-Baptiste', $client->firstName, 'les données maîtres ne sont pas écrasées');
        self::assertInstanceOf(ClientAddedToWorkspaceEvent::class, $captured);
        self::assertFalse($captured->wasCreated);
    }

    public function testIsIdempotentWhenClientAlreadyInThePortfolio(): void
    {
        $existing = Client::initiate('jean.dupont@example.com', 'Jean', 'Dupont', isActif: true);
        $existing->attachToWorkspace($this->workspace);
        $this->clientRepository->method('findByEmail')->willReturn($existing);

        $this->clientRepository->expects($this->never())->method('save');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $client = ($this->useCase)($this->request());

        self::assertSame($existing, $client);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testTurnsAConcurrentCreationRaceIntoARetryableDomainException(): void
    {
        // Deux membres créent le même e-mail en même temps : notre findByEmail
        // renvoie null, mais le save heurte la contrainte unique.
        $this->clientRepository->method('findByEmail')->willReturn(null);
        $this->clientRepository->method('save')
            ->willThrowException($this->createStub(UniqueConstraintViolationException::class));

        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->expectException(\DomainException::class);
        ($this->useCase)($this->request());
    }
}
