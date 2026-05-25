<?php

namespace App\Tests\Application\User;

use App\Application\User\DTO\Request\LoginUserRequest;
use App\Application\User\UseCase\SendLoginUserUseCase;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\User\Message\SendMagicLinkMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SendLoginUserUseCaseTest extends TestCase
{
    private UserRepositoryInterface|MockObject $userRepositoryMock;
    private MessageBusInterface|MockObject $messageBusMock;
    private UrlGeneratorInterface|MockObject $routerMock;
    private SendLoginUserUseCase $useCase;

    protected function setUp(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->messageBusMock = $this->createMock(MessageBusInterface::class);
        $this->routerMock = $this->createMock(UrlGeneratorInterface::class);

        $this->useCase = new SendLoginUserUseCase(
            $this->userRepositoryMock,
            $this->messageBusMock,
            $this->routerMock
        );
    }

    public function testItGeneratesAndSendsMagicLinkSuccessfully(): void
    {
        // --- ARRANGE ---
        $request = new LoginUserRequest();
        $request->email = 'test@example.com';

        // 🪄 LA CORRECTION : On instancie un VRAI utilisateur
        // (J'utilise ta méthode statique vue dans tes précédents messages)
        $realUser = User::create('test@example.com', 'John', 'Doe');

        $this->userRepositoryMock
            ->expects($this->once())
            ->method('getByEmail')
            ->with($request->email)
            ->willReturn($realUser); // On retourne le vrai objet

        $this->userRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($realUser);

        $expectedUrl = 'https://app.example.com/magic-link/dynamic_token';

        $this->routerMock
            ->expects($this->once())
            ->method('generate')
            ->with(
                'app_verify_magic_link',
                // 🪄 MAGIE : On vérifie que le paramètre 'token' correspond à celui qui vient d'être généré par l'entité
                $this->callback(fn(array $params) => isset($params['token']) && $params['token'] === $realUser->magicLinkToken),
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn($expectedUrl);

        $this->messageBusMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (SendMagicLinkMessage $message) use ($realUser, $expectedUrl) {
                // On vérifie le message avec les valeurs réelles de l'entité
                $this->assertSame($realUser->email, $message->userEmail);
                $this->assertSame($realUser->magicLinkToken, $message->magicLinkToken);
                $this->assertSame($expectedUrl, $message->magicLinkUrl);

                return true;
            }))
            ->willReturn(new Envelope(new \stdClass()));

        // --- ACT ---
        ($this->useCase)($request);

        // --- ASSERT ---
        // Le simple fait que le code arrive ici prouve que les assertions
        // de ton UseCase (Assert::notNull) ont validé la vraie entité.
    }
}
