<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Voter;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Autorise la révocation d'un accusé de réception du DER.
 *
 * Règle : être **administrateur** du cabinet propriétaire du dossier ET avoir le
 * droit de consulter ce dossier. Un accusé est une pièce de preuve — sa
 * révocation ne doit pas être ouverte à tout collaborateur.
 *
 * @extends Voter<string, ComplianceFolder>
 */
class RevokeDerAcknowledgementVoter extends Voter
{
    public const string REVOKE = 'REVOKE_DER_ACKNOWLEDGEMENT';

    public function __construct(
        private readonly WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::REVOKE === $attribute && $subject instanceof ComplianceFolder;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var ComplianceFolder $folder */
        $folder = $subject;

        if (!$this->workspaceMemberRepository->isUserAdminOfWorkspace($user, $folder->workspace)) {
            return false;
        }

        return $folder->canBeViewedBy($user);
    }
}
