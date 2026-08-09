<?php

declare(strict_types=1);

namespace App\Application\Tracking\UseCase;

use App\Application\Tracking\DTO\Request\ClickTrackingDto;
use App\Domain\Tracking\Entity\ClickLog;
use App\Domain\Tracking\Repository\ClickLogRepositoryInterface;
use Webmozart\Assert\Assert;

readonly class TrackClickUseCase
{
    public function __construct(
        private ClickLogRepositoryInterface $clickLogRepository,
    ) {
    }

    public function execute(ClickTrackingDto $dto): void
    {
        // 1. Règle métier : Anonymisation de l'IP (RGPD / CNIL)
        // On remplace le dernier bloc de l'IPv4 par un 0 (ex: 192.168.1.45 -> 192.168.1.0)
        $anonymizedIp = $dto->ipAddress;

        if (null !== $anonymizedIp) {
            // On remplace le dernier bloc de l'IPv4 par un 0
            $anonymizedIp = preg_replace('/(\d+)\.(\d+)\.(\d+)\.(\d+)/', '$1.$2.$3.0', $anonymizedIp);
        }

        Assert::notNull($dto->elementName);
        Assert::notNull($dto->pageUrl);
        // 2. Création de l'entité via le Named Constructor
        $log = ClickLog::create(
            elementName: $dto->elementName,
            pageUrl: $dto->pageUrl,
            referrer: $dto->referrer,
            userAgent: $dto->userAgent,
            ipAddress: $anonymizedIp,
            resolution: $dto->screenResolution,
            locale: $dto->locale,
            // Extraction sécurisée des UTMs depuis le tableau du DTO
            utmSource: $dto->utmData['source'] ?? null,
            utmMedium: $dto->utmData['medium'] ?? null,
            utmCampaign: $dto->utmData['campaign'] ?? null,
            sessionId: $dto->sessionId
        );

        // 3. Persistance via l'interface du Repository (avec flush = true)
        $this->clickLogRepository->save($log, true);
    }
}
