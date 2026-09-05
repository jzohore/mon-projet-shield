<?php

declare(strict_types=1);

namespace App\Tests\Application\Portal;

use App\Application\Portal\DTO\Request\UpdateClientProfileRequest;
use App\Application\Portal\UseCase\UpdateClientProfileUseCase;
use App\Domain\User\Entity\Client;
use App\Domain\User\Repository\ClientRepositoryInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdateClientProfileUseCaseTest extends TestCase
{
    private ClientRepositoryInterface&MockObject $clientRepository;
    private UpdateClientProfileUseCase $useCase;

    protected function setUp(): void
    {
        $this->clientRepository = $this->createMock(ClientRepositoryInterface::class);
        $this->useCase = new UpdateClientProfileUseCase($this->clientRepository);
    }

    private function client(): Client
    {
        return Client::initiate('client@example.test', 'Jean', 'Dupont');
    }

    public function testUpdatesTheProfileAndPersistsWhenSomethingChanged(): void
    {
        $client = $this->client();

        $request = new UpdateClientProfileRequest();
        $request->firstName = ' Jeanne ';
        $request->lastName = 'Dupont';
        $request->phoneNumber = ' 06 12 34 56 78 ';

        $this->clientRepository->expects($this->once())->method('save')->with($client);

        ($this->useCase)($client, $request);

        self::assertSame('Jeanne', $client->firstName);
        self::assertSame('Dupont', $client->lastName);
        self::assertSame('06 12 34 56 78', $client->phoneNumber);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testDoesNothingWhenNothingChanged(): void
    {
        $client = $this->client();

        $request = new UpdateClientProfileRequest();
        $request->firstName = 'Jean';
        $request->lastName = 'Dupont';
        $request->phoneNumber = null;

        $this->clientRepository->expects($this->never())->method('save');

        ($this->useCase)($client, $request);
    }

    public function testNormalisesAnEmptyPhoneToNull(): void
    {
        $client = $this->client();

        $request = new UpdateClientProfileRequest();
        $request->firstName = 'Jean';
        $request->lastName = 'Martin';
        $request->phoneNumber = '   ';

        $this->clientRepository->expects($this->once())->method('save');

        ($this->useCase)($client, $request);

        self::assertNull($client->phoneNumber);
    }
}
