<?php

namespace App\Application\Tracking\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class ClickTrackingDto
{
    #[Assert\NotBlank(message: "L'élément tracké est obligatoire.")]
    #[Assert\Length(max: 100)]
    public ?string $elementName = null;

    #[Assert\NotBlank(message: "L'URL de la page est obligatoire.")]
    #[Assert\Url(message: "L'URL de la page doit être valide.", requireTld: false)]
    public ?string $pageUrl = null;

    #[Assert\Url(message: "L'URL de la page doit être valide.", requireTld: false)]
    public ?string $referrer = null;

    #[Assert\Regex(
        pattern: '/^\d+x\d+$/',
        message: "La résolution doit être au format LargeurxHauteur (ex: 1920x1080)."
    )]
    public ?string $screenResolution = null;

    #[Assert\Length(max: 1000)]
    public ?string $userAgent = null;

    #[Assert\Ip(version: 'all', message: "L'adresse IP n'est pas valide.")]
    public ?string $ipAddress = null;

    #[Assert\Locale(message: "La locale n'est pas valide.")]
    public ?string $locale = null;

    /**
     * @var array<string, string>|null
     */
    #[Assert\Type(type: 'array', message: "Les données UTM doivent être un tableau.")]
    public ?array $utmData = null;

    #[Assert\Length(max: 255)]
    public ?string $sessionId = null;
}
