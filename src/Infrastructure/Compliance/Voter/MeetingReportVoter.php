<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Voter;

use App\Domain\Compliance\Entity\ComplianceFolder;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Autorise la validation et la révocation du rapport d'entretien IA d'un dossier.
 *
 * Règle : être membre du cabinet propriétaire du dossier ET avoir le droit de
 * consulter ce dossier (liste blanche de confidentialité respectée). Les deux
 * rôles CGP — admin et collaborateur — peuvent valider / révoquer : un CGP doit
 * pouvoir figer sa propre synthèse puis la corriger via une nouvelle version.
 *
 * @extends Voter<string, ComplianceFolder>
 */
class MeetingReportVoter extends Voter
{
    public const string VALIDATE = 'VALIDATE_MEETING_REPORT';
    public const string REVOKE = 'REVOKE_MEETING_REPORT';

    public function __construct(
        private readonly WorkspaceMemberRepositoryInterface $workspaceMemberRepository,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VALIDATE, self::REVOKE], true)
            && $subject instanceof ComplianceFolder;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var ComplianceFolder $folder */
        $folder = $subject;

        if (!$this->isMemberOfWorkspace($user, $folder->workspace)) {
            return false;
        }

        return match ($attribute) {
            self::VALIDATE, self::REVOKE => $folder->canBeViewedBy($user),
            default => false,
        };
    }

    private function isMemberOfWorkspace(User $user, Workspace $workspace): bool
    {
        return $this->workspaceMemberRepository->findByWorkspaceAndUser($workspace, $user) instanceof WorkspaceMember;
    }
}
