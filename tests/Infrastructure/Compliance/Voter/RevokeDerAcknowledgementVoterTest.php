<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Compliance\Voter;

use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Infrastructure\Compliance\Voter\RevokeDerAcknowledgementVoter;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class RevokeDerAcknowledgementVoterTest extends TestCase
{
    use ReflectionHelperTrait;

    private WorkspaceMemberRepositoryInterface&Stub $workspaceMemberRepository;
    private RevokeDerAcknowledgementVoter $voter;

    protected function setUp(): void
    {
        $this->workspaceMemberRepository = $this->createStub(WorkspaceMemberRepositoryInterface::class);
        $this->voter = new RevokeDerAcknowledgementVoter($this->workspaceMemberRepository);
    }

    private function token(?User $user): TokenInterface&Stub
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    private function folder(): BusinessFolder
    {
        $workspace = $this->createEntityState(Workspace::class, ['slugId' => 'wrk_1', 'name' => 'Cabinet']);

        return $this->createEntityState(BusinessFolder::class, [
            'slugId' => 'comp_fol_1',
            'workspace' => $workspace,
            'restrictedUsers' => new \Doctrine\Common\Collections\ArrayCollection(),
        ]);
    }

    public function testAbstainsOnUnsupportedAttributeOrSubject(): void
    {
        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token($this->createEntityState(User::class, [])), $this->folder(), ['SOMETHING_ELSE'])
        );
        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token($this->createEntityState(User::class, [])), new \stdClass(), [RevokeDerAcknowledgementVoter::REVOKE])
        );
    }

    public function testDeniesAnonymousAndNonAdminUsers(): void
    {
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token(null), $this->folder(), [RevokeDerAcknowledgementVoter::REVOKE])
        );

        $this->workspaceMemberRepository->method('isUserAdminOfWorkspace')->willReturn(false);
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token($this->createEntityState(User::class, [])), $this->folder(), [RevokeDerAcknowledgementVoter::REVOKE])
        );
    }

    public function testGrantsAWorkspaceAdminWhoCanViewTheFolder(): void
    {
        $this->workspaceMemberRepository->method('isUserAdminOfWorkspace')->willReturn(true);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->token($this->createEntityState(User::class, [])), $this->folder(), [RevokeDerAcknowledgementVoter::REVOKE])
        );
    }
}
