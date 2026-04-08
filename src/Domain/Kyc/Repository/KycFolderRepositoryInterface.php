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

    /**
     * @param string $email
     * @param array<KycFolderStatus> $statuses
     * @param string $workspaceId
     * @return KycFolder|null
     */
    public function findFirstByEmailAndStatuses(string $email, array $statuses, string $workspaceId): ?KycFolder;

    /**
     * @return Pagerfanta<KycFolder>
     */
    public function getKycFolderList(string $workspaceSlugId, ?string $search = null): Pagerfanta;

    public function remove(KycFolder $kycFolder): void;

    public function countDraftsForWorkspace(string $workspaceId): int;
}
