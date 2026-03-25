<?php

namespace App\Application\Kyc\DTO\Request;

class BindCompanyToKycFolderRequest
{
    public string $companyName;
    public string $companySiret;
    public string $companySiren;
    public string $companyLegalCategory;
    public string $companyAddress;
    public string $statusAdministratif;
    public string $folderSlugId;
}
