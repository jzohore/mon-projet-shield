<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Enum\AuditEventType;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;

use function Symfony\Component\Clock\now;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

/**
 * Connexion support (impersonation) : impose un motif renseigné au préalable
 * (formulaire dédié → session) et trace l'entrée comme la sortie dans le journal
 * d'audit du cabinet concerné, visible côté cabinet. Sans motif, le switch est refusé.
 */
#[AsEventListener(event: SwitchUserEvent::class)]
final readonly class SwitchUserAuditListener
{
    private const string EXIT_VALUE = '_exit';
    private const string KEY_REASON = '_support_impersonation_reason';
    private const string KEY_OPERATOR = '_support_impersonation_operator';
    private const string KEY_TARGET = '_support_impersonation_target';
    private const string KEY_STARTED_AT = '_support_impersonation_started_at';

    public function __construct(
        private AuditLogRepositoryInterface $auditLogRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(SwitchUserEvent $event): void
    {
        $request = $event->getRequest();
        $session = $request->hasSession() ? $request->getSession() : null;

        if (self::EXIT_VALUE === $request->query->get('_switch_user')) {
            $this->recordExit($session);

            return;
        }

        $target = $event->getTargetUser();
        if (!$target instanceof User) {
            return;
        }

        $reason = $session instanceof SessionInterface ? trim((string) $session->get(self::KEY_REASON, '')) : '';
        if (mb_strlen($reason) < 10) {
            throw new AccessDeniedException('Connexion support impossible : un motif d\'accès (10 caractères minimum) doit être saisi via le formulaire dédié.');
        }

        $token = $event->getToken();
        $operator = $token instanceof SwitchUserToken ? $token->getOriginalToken()->getUser() : null;
        $operatorEmail = $operator?->getUserIdentifier() ?? 'inconnu';
        $operatorName = $operator instanceof User ? $operator->getFullName() : $operatorEmail;

        $startedAt = now();

        $this->auditLogRepository->save(AuditLog::initiate(
            eventName: AuditEventType::ADMIN_IMPERSONATION_START,
            payload: $this->payload($operatorEmail, $operatorName, $target->email, $target->getFullName(), $reason, ['at' => $startedAt->format(\DateTimeInterface::ATOM)]),
            workspace: $target->workspace,
        ));

        $session->set(self::KEY_OPERATOR, ['email' => $operatorEmail, 'name' => $operatorName]);
        $session->set(self::KEY_TARGET, ['email' => $target->email, 'name' => $target->getFullName()]);
        $session->set(self::KEY_STARTED_AT, $startedAt->format(\DateTimeInterface::ATOM));
    }

    private function recordExit(?SessionInterface $session): void
    {
        if (!$session instanceof SessionInterface || !$session->has(self::KEY_OPERATOR)) {
            return;
        }

        /** @var array{email: string, name: string} $op */
        $op = $session->get(self::KEY_OPERATOR);
        /** @var array{email: string, name: string} $tgt */
        $tgt = $session->get(self::KEY_TARGET, ['email' => '', 'name' => '']);
        $startedAt = (string) $session->get(self::KEY_STARTED_AT, '');
        $reason = (string) $session->get(self::KEY_REASON, '');

        $impersonated = '' !== $tgt['email'] ? $this->userRepository->findByEmail($tgt['email']) : null;
        $workspace = $impersonated instanceof User ? $impersonated->workspace : null;

        $this->auditLogRepository->save(AuditLog::initiate(
            eventName: AuditEventType::ADMIN_IMPERSONATION_EXIT,
            payload: $this->payload($op['email'], $op['name'], $tgt['email'], $tgt['name'], $reason, [
                'started_at' => $startedAt,
                'ended_at' => now()->format(\DateTimeInterface::ATOM),
            ]),
            workspace: $workspace instanceof Workspace ? $workspace : null,
        ));

        foreach ([self::KEY_REASON, self::KEY_OPERATOR, self::KEY_TARGET, self::KEY_STARTED_AT] as $key) {
            $session->remove($key);
        }
    }

    /**
     * @param array<string, string> $extra
     *
     * @return array<string, mixed>
     */
    private function payload(string $operatorEmail, string $operatorName, string $targetEmail, string $targetName, string $reason, array $extra): array
    {
        return array_merge([
            'operator_email' => $operatorEmail,
            'operator_name' => $operatorName,
            'impersonated_email' => $targetEmail,
            'impersonated_name' => $targetName,
            'reason' => $reason,
        ], $extra);
    }
}
