<?php

namespace App\Application\Compliance\UseCase;

use App\Application\Compliance\DTO\Request\SetIndividualClientRequest;
use App\Domain\Compliance\Entity\IndividualFolder;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;

readonly class SetIndividualClientUseCase
{
    public function __construct(
        private ComplianceFolderRepositoryInterface $repository,
    ) {}

    public function __invoke(SetIndividualClientRequest $request): void
    {
        $folder = $this->repository->findByReference($request->reference);

        if (!$folder instanceof IndividualFolder) {
            throw new \LogicException('Ce dossier n\'est pas un dossier physique (IndividualFolder).');
        }

        $folder->setClientInfo(
            firstName: $request->firstName,
            lastName: $request->lastName,
            email: $request->email
        );

        $this->repository->save($folder);
    }
}
