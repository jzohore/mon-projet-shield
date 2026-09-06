<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Security;

use App\Domain\User\Entity\Client;
use App\Infrastructure\Security\ClientStatusChecker;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class ClientStatusCheckerTest extends TestCase
{
    use ReflectionHelperTrait;

    private ClientStatusChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new ClientStatusChecker();
    }

    public function testLetsAnActiveClientThrough(): void
    {
        $client = $this->createEntityState(Client::class, ['isActif' => true]);

        $this->checker->checkPreAuth($client);
        $this->checker->checkPostAuth($client);

        $this->expectNotToPerformAssertions();
    }

    public function testBlocksADeactivatedClient(): void
    {
        $client = $this->createEntityState(Client::class, ['isActif' => false]);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->checker->checkPreAuth($client);
    }

    public function testIgnoresNonClientUsers(): void
    {
        $this->checker->checkPreAuth(new InMemoryUser('x', null));

        $this->expectNotToPerformAssertions();
    }
}
