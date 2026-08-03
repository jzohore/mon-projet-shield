<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Command;

use App\Domain\User\Entity\Admin;
use App\Domain\User\Repository\AdminRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:admin:seed',
    description: 'Seed l\'administrateur initial de la plateforme Kysure.',
)]
final readonly class SeedAdminCommand
{
    public function __construct(
        private AdminRepositoryInterface $adminRepository,
    ) {
    }

    public function __invoke(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = 'kysure-junior@gmail.com';

        $existingAdmin = $this->adminRepository->findByEmail($email);
        if ($existingAdmin instanceof Admin) {
            $io->warning(sprintf('L\'administrateur "%s" existe déjà.', $email));

            return Command::SUCCESS;
        }

        $admin = Admin::initiate(
            email: $email,
            firstName: 'Zohore',
            lastName: 'Junior',
            isActif: true,
        );

        $this->adminRepository->save($admin, flush: true);

        $io->success(sprintf('Administrateur créé avec succès : %s', $admin->email));

        return Command::SUCCESS;
    }
}
