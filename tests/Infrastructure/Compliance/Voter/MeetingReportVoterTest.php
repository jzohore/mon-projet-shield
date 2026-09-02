<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Compliance\Voter;

use App\Domain\Compliance\Entity\BusinessFolder;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Infrastructure\Compliance\Voter\MeetingReportVoter;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class MeetingReportVoterTest extends TestCase
{
    use ReflectionHelperTrait;

    private WorkspaceMemberRepositoryInterface&Stub $memberRepository;
    private MeetingReportVoter $voter;
    private Workspace $workspace;
    private User $user;

    protected function setUp(): void
    {
        $this->memberRepository = $this->createStub(WorkspaceMemberRepositoryInterface::class);
        $this->voter = new MeetingReportVoter($this->memberRepository);
        $this->workspace = $this->createEntityState(Workspace::class);
        $this->user = $this->createEntityState(User::class, [
            'firstName' => 'marie',
            'lastName' => 'curie',
            'email' => 'marie@kysure.test',
        ]);
    }

    public function testAbstainsOnUnsupportedAttribute(): void
    {
        self::assertSame(
            Voter::ACCESS_ABSTAIN,
            $this->voter->vote($this->token($this->user), $this->folder(), ['EDIT']),
        );
    }

    public function testAbstainsWhenSubjectIsNotAComplianceFolder(): void
    {
        self::assertSame(
            Voter::ACCESS_ABSTAIN,
            $this->voter->vote($this->token($this->user), new \stdClass(), [MeetingReportVoter::VALIDATE]),
        );
    }

    public function testDeniesWhenTokenCarriesNoUser(): void
    {
        self::assertSame(
            Voter::ACCESS_DENIED,
            $this->voter->vote($this->token(null), $this->folder(), [MeetingReportVoter::VALIDATE]),
        );
    }

    public function testDeniesWhenUserIsNotAMemberOfTheWorkspace(): void
    {
        $this->memberRepository->method('findByWorkspaceAndUser')->willReturn(null);

        $token = $this->token($this->user);
        self::assertSame(Voter::ACCESS_DENIED, $this->voter->vote($token, $this->folder(), [MeetingReportVoter::VALIDATE]));
        self::assertSame(Voter::ACCESS_DENIED, $this->voter->vote($token, $this->folder(), [MeetingReportVoter::REVOKE]));
    }

    public function testGrantsToAMemberAbleToViewTheFolder(): void
    {
        $this->memberRepository->method('findByWorkspaceAndUser')
            ->willReturn($this->createEntityState(WorkspaceMember::class));

        $token = $this->token($this->user);
        self::assertSame(Voter::ACCESS_GRANTED, $this->voter->vote($token, $this->folder(), [MeetingReportVoter::VALIDATE]));
        self::assertSame(Voter::ACCESS_GRANTED, $this->voter->vote($token, $this->folder(), [MeetingReportVoter::REVOKE]));
    }

    public function testDeniesOnAConfidentialFolderWhenUserIsNotWhitelisted(): void
    {
        $this->memberRepository->method('findByWorkspaceAndUser')
            ->willReturn($this->createEntityState(WorkspaceMember::class));

        $confidentialFolder = $this->createEntityState(BusinessFolder::class, [
            'workspace' => $this->workspace,
            'isConfidential' => true,
            'restrictedUsers' => new ArrayCollection(),
        ]);

        self::assertSame(
            Voter::ACCESS_DENIED,
            $this->voter->vote($this->token($this->user), $confidentialFolder, [MeetingReportVoter::VALIDATE]),
        );
    }

    private function folder(): BusinessFolder
    {
        return $this->createEntityState(BusinessFolder::class, [
            'workspace' => $this->workspace,
            'isConfidential' => false,
        ]);
    }

    private function token(?User $user): TokenInterface&Stub
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }
}
