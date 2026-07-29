<?php

declare(strict_types=1);

namespace App\Application\Firm\DTO\Request;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class UploadLogoRequest
{
    #[Assert\NotBlank(message: 'Veuillez sélectionner un fichier.')]
    #[Assert\Image(
        maxSize: '2M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp'],
        maxRatio: 2.0,
        minRatio: 0.5, // Évite les images ultra-panoramiques qui cassent le design
        mimeTypesMessage: 'Le logo doit être au format JPG, PNG, WEBP ou SVG.'  // Évite les images ultra-verticales
    )]
    public ?UploadedFile $logoFile = null;
}
