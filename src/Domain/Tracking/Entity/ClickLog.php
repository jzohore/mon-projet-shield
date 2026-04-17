<?php

namespace App\Domain\Tracking\Entity;

use App\Infrastructure\Trait\GenerateSlugPrefixedTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'click_logs')]
class ClickLog
{
    use GenerateSlugPrefixedTrait;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null {
        get => $this->id;
    }

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public private(set) string $slugId;

    #[ORM\Column(length: 100)]
    public private(set) string $elementName;

    #[ORM\Column(type: Types::TEXT)]
    public private(set) string $pageUrl;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public private(set) ?string $referrer;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public private(set) ?string $userAgent;

    #[ORM\Column(length: 45, nullable: true)]
    public private(set) ?string $ipAddress;

    #[ORM\Column(length: 20, nullable: true)]
    public private(set) ?string $resolution;

    #[ORM\Column(length: 10, nullable: true)]
    public private(set) ?string $locale;

    #[ORM\Column(length: 100, nullable: true)]
    public private(set) ?string $utmSource;

    #[ORM\Column(length: 100, nullable: true)]
    public private(set) ?string $utmMedium;

    #[ORM\Column(length: 100, nullable: true)]
    public private(set) ?string $utmCampaign;

    #[ORM\Column(length: 255, nullable: true)]
    public private(set) ?string $sessionId;

    #[ORM\Column]
    public private(set) \DateTimeImmutable $createdAt;

    /**
     * Le constructeur est privé pour forcer l'utilisation de ClickLog::create()
     */
    private function __construct(
        string $elementName,
        string $pageUrl,
        ?string $referrer = null,
        ?string $userAgent = null,
        ?string $ipAddress = null,
        ?string $resolution = null,
        ?string $locale = null,
        ?string $utmSource = null,
        ?string $utmMedium = null,
        ?string $utmCampaign = null,
        ?string $sessionId = null
    ) {
        $this->elementName = $elementName;
        $this->pageUrl = $pageUrl;
        $this->referrer = $referrer;
        $this->userAgent = $userAgent;
        $this->ipAddress = $ipAddress;
        $this->resolution = $resolution;
        $this->locale = $locale;
        $this->utmSource = $utmSource;
        $this->utmMedium = $utmMedium;
        $this->utmCampaign = $utmCampaign;
        $this->sessionId = $sessionId;
        $this->slugId = $this->generate_ulid_prefixed('click_log_');
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Méthode statique de création (Named Constructor)
     */
    public static function create(
        string $elementName,
        string $pageUrl,
        ?string $referrer = null,
        ?string $userAgent = null,
        ?string $ipAddress = null,
        ?string $resolution = null,
        ?string $locale = null,
        ?string $utmSource = null,
        ?string $utmMedium = null,
        ?string $utmCampaign = null,
        ?string $sessionId = null
    ): self {
        return new self(
            $elementName,
            $pageUrl,
            $referrer,
            $userAgent,
            $ipAddress,
            $resolution,
            $locale,
            $utmSource,
            $utmMedium,
            $utmCampaign,
            $sessionId,
        );
    }
}
