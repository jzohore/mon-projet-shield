<?php

declare(strict_types=1);

namespace App\Infrastructure\EventSubscriber;

use App\Application\Audit\DTO\Request\CreateAuditLogRequest;
use App\Application\Audit\UseCase\CreateAuditLogUseCase;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\User\Entity\Client;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Service\DeviceDetectorService;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final readonly class UserLoginListener implements EventSubscriberInterface
{
    public function __construct(
        private DeviceDetectorService $deviceDetectorService,
        private UserRepositoryInterface $userRepository,
        private CreateAuditLogUseCase $auditLogUseCase,
    ) {
    }

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
        $authenticatable = $event->getPassport()->getUser();

        // On enregistre l'appareil pour TOUT LE MONDE (Sécurité LCB-FT / Anti-fraude)
        if ($authenticatable instanceof User) {
            // PHP 8.4 : Accès direct à la propriété readonly ou private(set)
            $this->deviceDetectorService->createDeviceDetector($authenticatable->slugId);
        }
    }

    public function onClearMagicLinkToken(LoginSuccessEvent $event): void
    {
        $user = $event->getPassport()->getUser();

        // Seuls les CGP (User) utilisent le système "Custom" de lien magique avec un token en DB.
        // Les clients utilisent le composant natif Symfony : aucune action en base requise !
        if ($user instanceof User) {
            $user->clearMagicLinkToken();
            $this->userRepository->save($user);
        }
    }

    /**
     * @throws \Exception
     */
    public function onAuditLogin(LoginSuccessEvent $event): void
    {
        $authenticatable = $event->getPassport()->getUser();

        if ($authenticatable instanceof User) {
            $this->auditCgpLogin($authenticatable);

            return;
        }

        if ($authenticatable instanceof Client) {
            $this->auditClientLogin($authenticatable);

            return;
        }
    }

    private function auditCgpLogin(User $user): void
    {
        $requestAuditLog = new CreateAuditLogRequest(
            eventName: AuditEventType::USER_LOGGED_IN,
            data: [
                'target_user_id' => $user->slugId,
                'email' => $user->email,
                'full_name' => $user->getFullName(), // Clone si objet, ou juste appel
                'account_type' => 'cgp',
            ],
            actorId: (string) $user->id, // Si tu peux récupérer le currentWorkspace, mets-le !
        );

        ($this->auditLogUseCase)($requestAuditLog);
    }

    private function auditClientLogin(Client $client): void
    {
        $requestAuditLog = new CreateAuditLogRequest(
            eventName: AuditEventType::USER_LOGGED_IN,
            data: [
                'target_client_id' => $client->slugId,
                'email' => $client->email,
                'full_name' => trim(sprintf('%s %s', $client->firstName, $client->lastName)),
                'account_type' => 'client_final',
            ],
            actorId: (string) $client->id,
        );

        ($this->auditLogUseCase)($requestAuditLog);
    }
}
