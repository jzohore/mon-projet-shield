<?php

namespace App\Domain\Kyc\Repository;

use App\Domain\Kyc\Entity\KycFolder;
use App\Domain\Kyc\Enum\KycFolderStatus;
use Pagerfanta\Pagerfanta;

interface KycFolderRepositoryInterface
{
    public function save(KycFolder $kycFolder): void;
    public function findBySlugId(string $slugId): ?KycFolder;
    public function findByToken(string $shareToken): ?KycFolder;

    public function findOneByEmailAndStatus(string $email, KycFolderStatus $status, string $workspaceId): ?KycFolder;

    /**
     * @return Pagerfanta<KycFolder>
     */
    public function getKycFolderList(string $workspaceSlugId, ?string $search = null): Pagerfanta;

    public function remove(KycFolder $kycFolder): void;
}
