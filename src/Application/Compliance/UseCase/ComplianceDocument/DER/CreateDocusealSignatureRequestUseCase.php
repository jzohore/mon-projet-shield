<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\ComplianceDocument\DER;

use App\Application\Compliance\UseCase\ComplianceFolder\ComplianceFolderShowAssembler;
use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Infrastructure\DocuSeal\DocuSealClient;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

readonly class CreateDocusealSignatureRequestUseCase
{
    public function __construct(
        private ComplianceFolderShowAssembler $assembler,
        private DocuSealClient $docuSealClient,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function __invoke(ComplianceFolder $folder): array
    {
        $dto = $this->assembler->assemble($folder);
        Assert::notNull($dto, 'Impossible d\'assembler les données du client pour la signature.');
        Assert::notEmpty($dto->contactEmail, 'L\'email du client est requis pour la signature électronique.');
        Assert::notEmpty($dto->contactName, 'Le nom du client est requis pour la signature.');

        $this->logger->info('Demande de création d\'enveloppe DocuSeal initiée.', [
            'folder_id' => (string) $folder->id,
            'email' => $dto->contactEmail,
        ]);

        $result = $this->docuSealClient->createSignatureRequest(
            $dto->contactEmail,
            $dto->contactName
        );

        // 🪄 Guard PHPStan 8 : Validation stricte du contrat d'interface externe
        Assert::isArray($result, 'La réponse de DocuSeal doit être un tableau.');
        Assert::keyExists($result, 'url', 'La réponse DocuSeal ne contient pas d\'URL de signature.');
        Assert::string($result['url'], 'L\'URL DocuSeal reçue n\'est pas une chaîne de caractères valide.');

        return $result;
    }
}
