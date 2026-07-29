<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceFolder;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\Compliance\Repository\ComplianceFolderRepositoryInterface;
use App\Domain\User\Entity\User;
use Webmozart\Assert\Assert;

readonly class MakeConfidentialUseCase
{
    public function __construct(
        private ComplianceFolderRepositoryInterface $repository,
    ) {
    }

    /**
     * @param User[] $allowedUsers
     */
    public function __invoke(ComplianceFolder $folder, array $allowedUsers, ?bool $isConfidential): void
    {
        if ($isConfidential) {
            Assert::notEmpty($allowedUsers, 'Vous devez spécifier au moins un utilisateur autorisé.');

            Assert::allIsInstanceOf($allowedUsers, User::class, 'La liste d\'accès contient des éléments invalides.');

            $folder->makeConfidential($allowedUsers);
        } else {
            $folder->unlock();
        }

        $this->repository->save($folder);
    }
}
