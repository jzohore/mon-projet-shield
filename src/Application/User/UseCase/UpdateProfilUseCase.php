<?php

declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Application\User\DTO\Request\UserProfilRequest;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Event\UserOnboardingCompletedEvent;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

final readonly class UpdateProfilUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function __invoke(UserProfilRequest $request): void
    {
        Assert::notNull($request->jobTitle);
        Assert::notNull($request->phoneNumber);
        Assert::notNull($request->userSlugId);
        Assert::notNull($request->lang);

        $user = $this->userRepository->findBySlug($request->userSlugId);

        Assert::isInstanceOf($user, User::class);
        $user->profile->jobTitle = $request->jobTitle;
        $user->profile->phoneNumber = $request->phoneNumber;
        $user->onboardingStatus = OnboardingStatus::COMPLETED;
        $user->isVerified = true;
        $user->isOwner = true;
        $user->lang = $request->lang;
        $user->isActif = true;
        $this->userRepository->save($user);

        $this->eventDispatcher->dispatch(new UserOnboardingCompletedEvent($user));
    }
}
