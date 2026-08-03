<?php

declare(strict_types=1);

namespace App\Tests\Application\User\Register;

use App\Domain\User\Event\UserRegisteredEvent;
use App\Infrastructure\User\Listener\Register\DispatchWelcomeEmailListener;
use App\Infrastructure\User\Message\SendWelcomeEmailMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class UserRegisteredEventListenerTest extends TestCase
{
    public function testItDispatchesWelcomeEmailMessageOnUserRegistration(): void
    {
        // --- ARRANGE ---
        $event = new UserRegisteredEvent(
            userId: 'usr_123456',
            email: 'john.doe@kysure.fr',
            fullName: 'John DOE',
            magicLinkToken: 'secret_token_abc'
        );

        // On mock le MessageBus pour intercepter l'envoi asynchrone
        $messageBusMock = $this->createMock(MessageBusInterface::class);
        $messageBusMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (SendWelcomeEmailMessage $message): bool {
                $this->assertSame('usr_123456', $message->userId);
                $this->assertSame('john.doe@kysure.fr', $message->email);
                $this->assertSame('John DOE', $message->fullName);
                $this->assertSame('secret_token_abc', $message->magicLinkToken);

                return true;
            }))
            ->willReturn(new Envelope(new SendWelcomeEmailMessage('usr_123456', 'john.doe@kysure.fr', 'John DOE', 'secret_token_abc')));

        $listener = new DispatchWelcomeEmailListener($messageBusMock);

        // --- ACT ---
        ($listener)($event);
    }
}
