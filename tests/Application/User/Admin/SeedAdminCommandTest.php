<?php

declare(strict_types=1);

namespace App\Tests\Application\User\Admin;

use App\Domain\User\Entity\Admin;
use App\Domain\User\Repository\AdminRepositoryInterface;
use App\Infrastructure\User\Command\SeedAdminCommand;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class SeedAdminCommandTest extends TestCase
{
    private AdminRepositoryInterface&MockObject $adminRepositoryMock;

    protected function setUp(): void
    {
        $this->adminRepositoryMock = $this->createMock(AdminRepositoryInterface::class);
    }

    public function testItSeedsAdminSuccessfullyWhenNotExists(): void
    {
        // --- ARRANGE ---
        $email = 'kysure-junior@gmail.com';

        // 1. L'admin n'existe pas
        $this->adminRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        // 2. Sauvegarde de la nouvelle entité Admin
        $this->adminRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with(
                $this->callback(function (Admin $admin) use ($email): bool {
                    $this->assertSame($email, $admin->email);
                    $this->assertSame('Zohore', $admin->firstName);
                    $this->assertSame('Junior', $admin->lastName);
                    $this->assertTrue($admin->isActif);

                    return true;
                }),
                true
            );

        $command = new SeedAdminCommand($this->adminRepositoryMock);
        $tester = new CommandTester($command);

        // --- ACT ---
        $exitCode = $tester->execute([]);

        // --- ASSERT ---
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Administrateur créé avec succès : kysure-junior@gmail.com', $tester->getDisplay());
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testItSkipsCreationIfAdminAlreadyExists(): void
    {
        // --- ARRANGE ---
        $email = 'kysure-junior@gmail.com';
        $existingAdmin = $this->createMock(Admin::class);

        $this->adminRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($existingAdmin);

        // La méthode save ne doit jamais être appelée
        $this->adminRepositoryMock
            ->expects($this->never())
            ->method('save');

        $command = new SeedAdminCommand($this->adminRepositoryMock);
        $tester = new CommandTester($command);

        // --- ACT ---
        $exitCode = $tester->execute([]);

        // --- ASSERT ---
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('L\'administrateur "kysure-junior@gmail.com" existe déjà.', $tester->getDisplay());
    }
}
