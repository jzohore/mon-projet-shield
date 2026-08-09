<?php

declare(strict_types=1);

namespace App\Application\Firm\UseCase;

use App\Domain\Firm\Event\UpdateCounselorSignatureEvent;
use App\Domain\Firm\Exception\ProfileNotFoundException;
use App\Domain\Firm\Repository\RegulatoryProfileRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

readonly class UpdateCounselorSignatureUseCase
{
    public function __construct(
        private RegulatoryProfileRepositoryInterface $profileRepository,
        private CurrentWorkspaceProvider $workspaceProvider,
        private EventDispatcherInterface $eventDispatcher,
        private CurrentUserProvider $currentUserProvider,
    ) {
    }

    public function __invoke(string $signatureBase64): void
    {
        // 🪄 1. Sécurité basique : on s'assure que c'est bien une image Base64
        Assert::startsWith($signatureBase64, 'data:image/png;base64,', 'Format de signature invalide.');
        Assert::lengthBetween($signatureBase64, 100, 500000, 'La signature est soit vide, soit trop lourde.');

        $workspace = $this->workspaceProvider->getWorkspace();
        $user = $this->currentUserProvider->getUser();
        $profile = $workspace->regulatoryProfile;

        if (!$profile instanceof \App\Domain\Firm\Entity\RegulatoryProfile) {
            throw ProfileNotFoundException::withWorkspaceName($workspace->name);
        }

        $profile->updateSignature($signatureBase64);

        $this->profileRepository->save($profile);
        $this->eventDispatcher->dispatch(new UpdateCounselorSignatureEvent($profile, $user));
    }
}
