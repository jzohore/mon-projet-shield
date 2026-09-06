<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\Client;

use App\Application\Compliance\DTO\Request\CreateClientRequest;
use App\Domain\Compliance\Event\ClientAddedToWorkspaceEvent;
use App\Domain\User\Entity\Client;
use App\Domain\User\Repository\ClientRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Ajoute un client au portefeuille du cabinet courant. S'il existe déjà dans
 * KYSURE (rattaché à un autre cabinet), on le rattache sans écraser ses données
 * maîtres et sans révéler qu'il existait — message identique dans les deux cas.
 */
readonly class CreateOrAttachClientUseCase
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
        private CurrentWorkspaceProvider $workspaceProvider,
        private CurrentUserProvider $userProvider,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(CreateClientRequest $request): Client
    {
        $workspace = $this->workspaceProvider->getWorkspace();
        $actor = $this->userProvider->getUser();
        $email = mb_strtolower(trim($request->email));

        $client = $this->clientRepository->findByEmail($email);
        $wasCreated = false;

        if (!$client instanceof Client) {
            $client = Client::initiate($email, trim($request->firstName), trim($request->lastName), isActif: true);
            $wasCreated = true;
        }

        if ($client->workspaces->contains($workspace)) {
            return $client; // déjà dans le portefeuille : no-op idempotent
        }

        $client->attachToWorkspace($workspace);
        $this->clientRepository->save($client);

        $this->eventDispatcher->dispatch(new ClientAddedToWorkspaceEvent(
            clientSlugId: $client->slugId,
            workspaceSlugId: $workspace->slugId,
            actorName: $actor->getFullName(),
            actorSlugId: $actor->slugId,
            wasCreated: $wasCreated,
        ));

        return $client;
    }
}
