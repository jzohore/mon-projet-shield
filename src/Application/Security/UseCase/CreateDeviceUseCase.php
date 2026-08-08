<?php

declare(strict_types=1);

namespace App\Application\Security\UseCase;

use App\Application\Security\DTO\CreateDeviceRequest;
use App\Domain\Device\Device;
use App\Domain\Device\DeviceRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use Psr\Log\LoggerInterface;

final readonly class CreateDeviceUseCase
{
    public function __construct(
        private DeviceRepositoryInterface $deviceRepository,
        private UserRepositoryInterface $userRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateDeviceRequest $request): void
    {
        $user = $this->userRepository->findBySlug($request->userSlugId);

        if (!$user instanceof User) {
            $this->logger->error(sprintf('[DeviceTracker] Aucun utilisateur trouvé pour le slug "%s"', $request->userSlugId));

            return;
        }

        // Création de l'entité avec tolérance sur les données GeoIP nulles (Resilience SecOps)
        $device = new Device(
            owner: $user,
            clientInfo: $request->clientInfo,
            clientOs: $request->clientOs,
            clientDeviceName: $request->clientDeviceName ?? 'Unknown Device',
            clientBrandName: $request->clientBrandName ?? 'Generic',
            clientIsBrowser: $request->clientIsBrowser,
            clientIsSmartphone: $request->clientIsSmartphone,
            addressIp: $request->addressIp,
            sessionId: $request->sessionId,
            countryIsoCode: $request->countryIsoCode ?? 'XX',
            cityName: $request->cityName ?? 'Inconnue',
            postalCode: $request->postalCode,
            latitude: $request->latitude,
            longitude: $request->longitude,
        );

        $this->deviceRepository->save($device);

        $this->logger->info(sprintf('[DeviceTracker] Appareil enregistré pour l\'utilisateur %s (%s - %s)',
            $user->getUserIdentifier(),
            $request->addressIp,
            $request->cityName ?? 'GeoIP N/A'
        ));
    }
}
