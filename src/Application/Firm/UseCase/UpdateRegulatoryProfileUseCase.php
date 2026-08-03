<?php

declare(strict_types=1);

namespace App\Application\Firm\UseCase;

use App\Application\Firm\DTO\Request\PartnerDTO;
use App\Application\Firm\DTO\Request\UpdateRegulatoryProfileRequest;
use App\Domain\Firm\Entity\RegulatoryProfile;
use App\Domain\Firm\Event\RegulatoryProfileUpdatedEvent;
use App\Domain\Firm\Repository\RegulatoryProfileRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Domain\Workspace\Service\CurrentWorkspaceProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class UpdateRegulatoryProfileUseCase
{
    public function __construct(
        private CurrentWorkspaceProvider $workspaceProvider,
        private CurrentUserProvider $currentUserProvider,
        private RegulatoryProfileRepositoryInterface $repository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(UpdateRegulatoryProfileRequest $request): void
    {
        $workspace = $this->workspaceProvider->getWorkspace();
        $user = $this->currentUserProvider->getUser();

        $profile = $workspace->regulatoryProfile;

        if (!$profile instanceof RegulatoryProfile) {
            $profile = RegulatoryProfile::initiate($workspace);
        }

        $partnersAsArray = array_map(static fn (PartnerDTO $partner): array => [
            'name' => $partner->name,
            'address' => $partner->address,
            'email' => $partner->email,
            'phone' => $partner->phone,
        ], $request->partners);

        $profile->update(
            oriasNumber: $request->oriasNumber,
            professionalAssociation: $request->professionalAssociation,
            rcProInsurer: $request->rcProInsurer,
            rcProPolicyNumber: $request->rcProPolicyNumber,
            isIndependent: $request->isIndependent,
            partners: $partnersAsArray
        );

        $this->repository->save($profile);

        $this->eventDispatcher->dispatch(new RegulatoryProfileUpdatedEvent($profile, $user));
    }
}
