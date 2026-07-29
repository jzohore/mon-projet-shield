<?php

declare(strict_types=1);

namespace App\Infrastructure\KYC\Twig\Components;

use App\Application\Kyc\DTO\Request\BindCompanyToKycFolderRequest;
use App\Application\Kyc\DTO\Request\StakeholderRequest;
use App\Application\Kyc\UseCase\BindCompanyToKycFolderUseCase;
use App\Application\Kyc\UseCase\CreateStakeHolderUseCase;
use App\Application\Kyc\UseCase\GetCurrentKycFolderUseCase;
use App\Application\Kyc\UseCase\ResetCompanyToKycFolderUseCase;
use App\Infrastructure\Service\SiretSearchService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Webmozart\Assert\Assert;

#[AsLiveComponent(
    name: 'BindCompanyToKycFolderFormComponent',
    template: 'components/Kyc/BindCompanyToKycFolderFormComponent.html.twig',
)]
class BindCompanyToKycFolderFormComponent
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp]
    public ?string $folderSlugId = null;

    #[LiveProp(writable: true)]
    public string $searchQuery = '';

    #[LiveProp(writable: true)]
    public ?string $companyName = null;

    #[LiveProp]
    public ?string $companySiret = null;

    #[LiveProp]
    public ?string $companySiren = null;

    #[LiveProp]
    public ?string $companyAddress = null;

    #[LiveProp]
    public ?string $companyLegalForm = null;

    #[LiveProp]
    public ?string $statusAdministratif = null;

    #[LiveProp]
    public bool $isInFormation = false;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[LiveProp]
    public array $prefilledStakeholders = [];

    #[LiveProp]
    public bool $isAlreadySavedInDatabase = false;

    public bool $hasApiError = false;

    public function __construct(
        private readonly SiretSearchService $siretSearchService,
        private readonly LoggerInterface $logger,
        private readonly BindCompanyToKycFolderUseCase $bindCompanyToKycFolderUseCase,
        private readonly CreateStakeHolderUseCase $createStakeHolderUseCase,
        private readonly UrlGeneratorInterface $router,
        private readonly GetCurrentKycFolderUseCase $getCurrentKycFolderUseCase,
        private readonly ResetCompanyToKycFolderUseCase $resetCompanyToKycFolderUseCase,
    ) {
    }

    public function mount(?string $folderSlugId = null): void
    {
        $this->folderSlugId = $folderSlugId;

        if (!$this->folderSlugId) {
            return;
        }

        try {
            $folder = ($this->getCurrentKycFolderUseCase)($this->folderSlugId);

            if ($folder->companyName) {
                $this->isAlreadySavedInDatabase = true;

                if ('IN_FORMATION' === $folder->siren || 'IN_FORMATION' === $folder->siret) {
                    $this->isInFormation = true;
                    $this->companyName = $folder->companyName;
                } else {
                    $this->companyName = $folder->companyName;
                    $this->companySiret = $folder->siret;
                    $this->companySiren = $folder->siren;
                    $this->companyAddress = $folder->siret; // If you store it
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to mount KYC Folder', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $stakeholders
     */
    #[LiveAction]
    public function selectCompany(
        #[LiveArg]
        string $name,
        #[LiveArg]
        string $siret,
        #[LiveArg]
        string $siren,
        #[LiveArg]
        string $address,
        #[LiveArg]
        string $legalform,
        #[LiveArg]
        string $administratif,
        #[LiveArg]
        array $stakeholders,
    ): void {
        $this->companyName = $name;
        $this->companySiret = $siret;
        $this->companyAddress = $address;
        $this->companyLegalForm = $legalform;
        $this->companySiren = $siren;
        $this->statusAdministratif = $administratif;
        $this->prefilledStakeholders = $stakeholders;

        $this->searchQuery = '';
    }

    #[LiveAction]
    public function resetCompany(): void
    {
        Assert::notNull($this->folderSlugId);
        $this->companyName = null;
        $this->companySiret = null;
        $this->companyAddress = null;
        $this->companyLegalForm = null;
        $this->companySiren = null;
        $this->isInFormation = false;
        $this->statusAdministratif = null;
        $this->prefilledStakeholders = [];
        $this->searchQuery = '';
        ($this->resetCompanyToKycFolderUseCase)($this->folderSlugId);
    }

    #[LiveAction]
    public function setInFormation(): void
    {
        $this->isInFormation = true;
        $this->searchQuery = '';
    }

    #[LiveAction]
    public function cancelInFormation(): void
    {
        $this->isInFormation = false;
        $this->companyName = null;
    }

    #[LiveAction]
    public function save(): ?RedirectResponse
    {
        $this->validate();

        $dto = new BindCompanyToKycFolderRequest();
        $dtoStakeholder = new StakeholderRequest();

        try {
            $dto->folderSlugId = $this->folderSlugId ?? '';
            $dto->companySiret = $this->companySiret ?? '';
            $dto->companyName = $this->companyName ?? '';
            $dto->companyAddress = $this->companyAddress ?? '';
            $dto->companyLegalCategory = $this->companyLegalForm ?? '';
            $dto->companySiren = $this->companySiren ?? '';
            $dto->statusAdministratif = $this->statusAdministratif ?? '';

            ($this->bindCompanyToKycFolderUseCase)($dto);

            $dtoStakeholder->folderSlugId = $this->folderSlugId ?? '';
            $dtoStakeholder->data = $this->prefilledStakeholders;
            ($this->createStakeHolderUseCase)($dtoStakeholder);
        } catch (\DomainException $e) {
            $this->logger->error('Erreur métier lors de l\'ajout de la compagnie au dossier KYC', [
                'companyName' => $this->companyName,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        return new RedirectResponse($this->router->generate('portal_kyc_stakeholders'));
    }

    /**
     * @return array<int, array{siret: string, name: string, address: string}>
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getResults(): array
    {
        if (strlen($this->searchQuery) < 3) {
            return [];
        }

        try {
            $this->hasApiError = false; // On reset l'erreur à chaque tentative

            return $this->siretSearchService->search($this->searchQuery);
        } catch (ClientException $e) {
            // Si l'API renvoie 429
            if (429 === $e->getResponse()->getStatusCode()) {
                $this->hasApiError = true;
            }

            return [];
        } catch (\Exception) {
            // Pour toute autre erreur (API down, etc.)
            return [];
        }
    }
}
