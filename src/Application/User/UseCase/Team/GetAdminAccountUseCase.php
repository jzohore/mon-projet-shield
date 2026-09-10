<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Team;

use App\Application\User\DTO\Response\AdminAccountView;
use App\Domain\User\Entity\Admin;
use App\Domain\User\Repository\AdminRepositoryInterface;
use Webmozart\Assert\Assert;

final readonly class GetAdminAccountUseCase
{
    public function __construct(
        private AdminRepositoryInterface $adminRepository,
    ) {
    }

    public function __invoke(string $slugId): AdminAccountView
    {
        $admin = $this->adminRepository->findBySlugId($slugId);
        Assert::isInstanceOf($admin, Admin::class, 'Compte de l\'équipe introuvable.');

        return AdminAccountView::fromEntity($admin);
    }
}
