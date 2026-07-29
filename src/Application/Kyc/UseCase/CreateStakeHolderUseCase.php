<?php

declare(strict_types=1);

namespace App\Application\Kyc\UseCase;

use App\Application\Kyc\DTO\Request\StakeholderRequest;
use App\Domain\Kyc\Entity\Stakeholder;
use App\Domain\Kyc\Enum\StakeholderRole;
use App\Domain\Kyc\Event\CreateStakeholderEvent;
use App\Domain\Kyc\Repository\KycFolderRepositoryInterface;
use App\Domain\Kyc\Repository\StakeholderRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

final readonly class CreateStakeHolderUseCase
{
    public function __construct(
        private KycFolderRepositoryInterface $kycFolderRepository,
        private StakeholderRepositoryInterface $stakeholderRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(StakeholderRequest $request): void
    {
        $currentKycFolder = $this->kycFolderRepository->findBySlugId($request->folderSlugId);
        Assert::notNull($currentKycFolder);

        foreach ($request->data as $apiDirigeant) {
            // 1. Traduction du rôle
            $qualite = $apiDirigeant['qualite'] ?? 'gérant';
            $roleEnum = StakeholderRole::fromApiRole($qualite);

            // 2. Sécurisation des noms (Gestion des personnes physiques ET morales)
            $prenoms = $apiDirigeant['prenoms'] ?? '';

            // Si 'nom' n'existe pas, on cherche 'denomination' (cas d'une entreprise dirigeante)
            $nom = $apiDirigeant['nom'] ?? $apiDirigeant['denomination'] ?? 'Nom inconnu';

            // (Optionnel) Si on n'a vraiment ni nom ni prénom, on passe au suivant pour ne pas polluer la base
            if (in_array(trim($prenoms), ['', '0'], true) && in_array(trim((string) $nom), ['', '0'], true)) {
                continue;
            }

            // 3. Création de l'entité
            $stakeholder = Stakeholder::createBeneficialOwner(
                $currentKycFolder,
                $prenoms,
                $nom,
                $roleEnum,
                0, // Pourcentage à 0 par défaut
            );

            $this->stakeholderRepository->save($stakeholder);
        }

        $this->eventDispatcher->dispatch(new CreateStakeholderEvent($currentKycFolder));
    }
}
