<?php

declare(strict_types=1);

namespace App\Application\Portal\UseCase;

use App\Application\Portal\DTO\Request\UpdateClientProfileRequest;
use App\Domain\User\Entity\Client;
use App\Domain\User\Repository\ClientRepositoryInterface;

readonly class UpdateClientProfileUseCase
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {
    }

    public function __invoke(Client $client, UpdateClientProfileRequest $request): void
    {
        $firstName = trim($request->firstName);
        $lastName = trim($request->lastName);
        $phoneNumber = null !== $request->phoneNumber ? trim($request->phoneNumber) : null;
        $phoneNumber = '' !== (string) $phoneNumber ? $phoneNumber : null;

        // Rien n'a bougé : on ne touche pas à la base.
        if ($firstName === $client->firstName
            && $lastName === $client->lastName
            && $phoneNumber === $client->phoneNumber
        ) {
            return;
        }

        $client->updateProfile($firstName, $lastName, $phoneNumber);
        $this->clientRepository->save($client);
    }
}
