<?php

declare(strict_types=1);

namespace App\Application\User\UseCase;

use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OnboardingStatus;
use App\Domain\User\Event\UserOnboardingCompletedEvent;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

/**
 * Marque l'onboarding comme réellement terminé (dernière étape : l'utilisateur a
 * cliqué « Accéder à mon espace »). C'est ICI, et pas au choix du plan, qu'on
 * émet {@see UserOnboardingCompletedEvent} (audit AMF « onboarding terminé »,
 * e-mail de confirmation, rafraîchissement de la session).
 *
 * Idempotent : un double POST (double-clic) ne rejoue ni l'audit ni l'e-mail.
 */
readonly class MarkAsOnboardingCompletedUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(User $user): void
    {
        Assert::notNull($user->id);

        // 🛡️ Idempotence : déjà terminé → on ne refait rien.
        if (OnboardingStatus::COMPLETED === $user->onboardingStatus) {
            return;
        }

        $member = $this->workspaceMemberRepository->findOneByUser($user->id);
        Assert::isInstanceOf(
            $member,
            WorkspaceMember::class,
            'Impossible de finaliser l\'onboarding : aucun espace de travail rattaché à l\'utilisateur.',
        );

        $user->markAsOnboardingCompleted();
        $this->userRepository->save($user);

        $this->eventDispatcher->dispatch(new UserOnboardingCompletedEvent($user, $member->workspace));
    }
}
