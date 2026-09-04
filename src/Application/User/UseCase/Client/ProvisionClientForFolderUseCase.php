<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Client;

use App\Application\Compliance\UseCase\ComplianceFolder\ComplianceFolderShowAssembler;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\User\Entity\Client;
use App\Domain\User\Exception\ClientNotFoundException;
use Webmozart\Assert\Assert;

/**
 * Garantit qu'un compte client existe et est rattaché au dossier de conformité.
 *
 * Conséquence obligatoire de l'accusé de réception du DER (et non un effet de
 * bord de listener) : appelé directement, dans la transaction du use case.
 * Idempotent : si le dossier a déjà un client, il est simplement retourné.
 */
readonly class ProvisionClientForFolderUseCase
{
    public function __construct(
        private ComplianceFolderShowAssembler $assembler,
        private AttachExistingClientUseCase $attachExistingClientUseCase,
        private CreateClientUseCase $createClientUseCase,
    ) {
    }

    public function __invoke(ComplianceFolder $folder): Client
    {
        if ($folder->client instanceof Client) {
            return $folder->client;
        }

        $dto = $this->assembler->assemble($folder);
        Assert::stringNotEmpty($dto->contactEmail, 'L\'email du client est obligatoire.');
        Assert::stringNotEmpty($dto->contactFirstName, 'Le prénom du client est obligatoire.');
        Assert::stringNotEmpty($dto->contactLastName, 'Le nom du client est obligatoire.');
        Assert::notNull($folder->workspace->slugId, 'Le workspace du dossier n\'a pas de slug.');

        try {
            $client = ($this->attachExistingClientUseCase)($dto->contactEmail, $folder->workspace->slugId);
        } catch (ClientNotFoundException) {
            $client = ($this->createClientUseCase)(
                $dto->contactEmail,
                $dto->contactFirstName,
                $dto->contactLastName,
                $folder->workspace->slugId,
            );
        }

        $folder->attachClient($client);

        return $client;
    }
}
