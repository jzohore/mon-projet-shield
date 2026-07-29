<?php

declare(strict_types=1);

namespace App\Application\Support\UseCase;

use App\Domain\Shared\Port\RealTimeNotifierInterface;
use App\Domain\Support\Entity\SupportMessage;
use App\Domain\Support\Enum\SupportSenderType;
use App\Domain\Support\Repository\SupportThreadRepositoryInterface;

final readonly class AutoResolveInactiveThreadsUseCase
{
    public function __construct(
        private SupportThreadRepositoryInterface $threadRepository,
        private RealTimeNotifierInterface $notifier,
    ) {
    }

    /**
     * @return array{warned: int, resolved: int} Bilan de l'exécution
     *
     * @throws \DateInvalidOperationException
     */
    public function execute(\DateInterval $warningInactivity, \DateInterval $closureGracePeriod): array
    {
        $now = new \DateTimeImmutable();
        $warnedCount = 0;
        $resolvedCount = 0;

        // =========================================================================
        // 1. CLÔTURE DÉFINITIVE (Délai de grâce expiré)
        // =========================================================================
        // Ex: "Maintenant - 10 minutes"
        $closureThreshold = $now->sub($closureGracePeriod);

        // Requête : Threads ouverts, inactifs depuis $closureThreshold, ET closureWarningSent = TRUE
        $threadsToClose = $this->threadRepository->findThreadsPendingClosure($closureThreshold);

        foreach ($threadsToClose as $thread) {
            $thread->resolve();
            $thread->resetClosureWarning(); // Nettoyage de l'état au cas où il serait rouvert

            $this->threadRepository->save($thread);
            ++$resolvedCount;
            $this->notifier->notify(
                topic: 'support_thread_' . $thread->slugId,
                payload: ['action' => 'new_message', 'sender' => SupportSenderType::ADMIN]
            );
        }

        // =========================================================================
        // 2. AVERTISSEMENT (Inactivité initiale atteinte)
        // =========================================================================
        // Ex: "Maintenant - 1h50"
        $warningThreshold = $now->sub($warningInactivity);

        // Requête : Threads ouverts, inactifs depuis $warningThreshold, ET closureWarningSent = FALSE
        $threadsToWarn = $this->threadRepository->findInactiveThreadsForWarning($warningThreshold);

        foreach ($threadsToWarn as $thread) {
            $lastMessage = $thread->messages->last();

            if ($lastMessage && SupportSenderType::ADMIN === $lastMessage->senderType) {
                $warningMessage = "Bonjour, sans retour de votre part d'ici quelques minutes, ce ticket sera automatiquement clôturé. N'hésitez pas à nous répondre si votre problème persiste !";
                SupportMessage::write($thread, SupportSenderType::ADMIN, $warningMessage);

                // 🛡️ CRITIQUE : On bascule l'état pour que le CRON ne le relance plus,
                // et pour qu'il passe dans l'Étape 1 au prochain cycle.
                $thread->markClosureWarningAsSent();

                $this->threadRepository->save($thread);
                ++$warnedCount;
                $this->notifier->notify(
                    topic: 'support_thread_' . $thread->slugId,
                    payload: ['action' => 'new_message', 'sender' => SupportSenderType::ADMIN]
                );
            }
        }

        return [
            'warned' => $warnedCount,
            'resolved' => $resolvedCount,
        ];
    }
}
