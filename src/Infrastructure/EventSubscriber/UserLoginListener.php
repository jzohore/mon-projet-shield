<?php

namespace App\Infrastructure\EventSubscriber;

use App\Application\Audit\DTO\Request\CreateAuditLogRequest;
use App\Application\Audit\UseCase\CreateAuditLogUseCase;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Service\DeviceDetectorService;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Webmozart\Assert\Assert;

final readonly class UserLoginListener implements EventSubscriberInterface
{
    public function __construct(
        private DeviceDetectorService $deviceDetectorService,
        private UserRepositoryInterface $userRepository,
        private CreateAuditLogUseCase $auditLogUseCase,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => [
                // 1. Priorité 20 : On sécurise le compte immédiatement en invalidant le lien
                ['onClearMagicLinkToken', 20],
                // 2. Priorité 10 : On enregistre le nouvel appareil
                ['onRecordDevice', 10],
                // 3. Priorité -10 : On log l'audit à la toute fin
                ['onAuditLogin', -10],
            ],
        ];
    }

    /**
     * @throws AddressNotFoundException
     * @throws InvalidDatabaseException
     */
    public function onRecordDevice(LoginSuccessEvent $event): void
    {
        $user = $event->getPassport()->getUser();
        Assert::isInstanceOf($user, User::class);
        $this->deviceDetectorService->createDeviceDetector($user->slugId);
    }

    public function onClearMagicLinkToken(LoginSuccessEvent $event): void
    {
        $user = $event->getPassport()->getUser();
        Assert::isInstanceOf($user, User::class);
        $user->clearMagicLinkToken();
        $this->userRepository->save($user);
    }

    public function onAuditLogin(LoginSuccessEvent $event): void
    {
        $user = $event->getPassport()->getUser();
        Assert::isInstanceOf($user, User::class);
        $auditLog = new CreateAuditLogRequest(
            eventName: AuditEventType::USER_LOGGED_IN,
            resourceId: $user->slugId,
            data: [
                'email' => $user->email,
                'first_name' => $user->firstName,
            ]
        );

        ($this->auditLogUseCase)($auditLog);
    }
}
