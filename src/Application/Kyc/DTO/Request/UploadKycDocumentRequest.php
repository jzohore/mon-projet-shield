<?php

namespace App\Application\Kyc\DTO\Request;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class UploadKycDocumentRequest
{
    #[Assert\NotBlank]
    public string $folderSlugId;

    #[Assert\NotBlank]
    public string $slotId;

    #[Assert\NotNull(message: 'Veuillez sélectionner un fichier.')]
    #[Assert\File(
        maxSize: '10M',
        mimeTypes: ['application/pdf', 'image/jpeg', 'image/png'],
        mimeTypesMessage: 'Veuillez uploader un PDF, un JPG ou un PNG valide.',
    )]
    public UploadedFile $file;
}
