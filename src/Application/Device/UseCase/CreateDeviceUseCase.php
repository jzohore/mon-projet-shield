<?php

declare(strict_types=1);

namespace App\Application\Device\UseCase;

use App\Application\Device\DTO\Request\CreateDeviceRequest;
use App\Domain\Device\Device;
use App\Domain\Device\DeviceRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use Webmozart\Assert\Assert;

final readonly class CreateDeviceUseCase
{
    public function __construct(
        private DeviceRepositoryInterface $deviceRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(CreateDeviceRequest $request): void
    {
        Assert::notNull($request->userSlugId, 'Le slug utilisateur est requis.');
        Assert::notNull($request->addressIp, 'L\'adresse IP est requise.');
        Assert::notNull($request->sessionId, 'L\'ID de session est requis.');

        Assert::isArray($request->clientInfo);
        Assert::notEmpty($request->clientInfo);
        Assert::isArray($request->clientOs);

        Assert::notNull($request->clientDeviceName);
        Assert::notNull($request->clientBrandName);

        Assert::boolean($request->clientIsBrowser);
        Assert::boolean($request->clientIsSmartphone);

        Assert::string($request->countryIsoCode);
        Assert::string($request->cityName);
        Assert::string($request->postalCode);

        Assert::float($request->latitude);
        Assert::float($request->longitude);

        $user = $this->userRepository->findBySlug($request->userSlugId);

        Assert::isInstanceOf($user, User::class, sprintf('Aucun utilisateur trouvé pour le slug "%s"', $request->userSlugId));

        $device = new Device(
            owner: $user,
            clientInfo: $request->clientInfo,
            clientOs: $request->clientOs,
            clientDeviceName: $request->clientDeviceName,
            clientBrandName: $request->clientBrandName,
            clientIsBrowser: $request->clientIsBrowser,
            clientIsSmartphone: $request->clientIsSmartphone,
            addressIp: $request->addressIp,
            sessionId: $request->sessionId,
            countryIsoCode: $request->countryIsoCode,
            cityName: $request->cityName,
            postalCode: $request->postalCode,
            latitude: $request->latitude,
            longitude: $request->longitude,
        );

        // 4. Persistance
        $this->deviceRepository->save($device);
    }
}
