<?php

declare(strict_types=1);

namespace App\Tests\Application\Workspace\Invitation;

use App\Application\Workspace\UseCase\Invitation\AcceptInvitationUseCase;
use App\Domain\Database\TransactionManagerInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Workspace\Entity\Workspace;
use App\Domain\Workspace\Entity\WorkspaceInvitation;
use App\Domain\Workspace\Entity\WorkspaceMember;
use App\Domain\Workspace\Enum\InvitationStatus;
use App\Domain\Workspace\Enum\InvitedRole;
use App\Domain\Workspace\Exception\CannotAcceptInvitationException;
use App\Domain\Workspace\Exception\InvitationAlreadyUsedException;
use App\Domain\Workspace\Exception\SeatLimitReachedException;
use App\Domain\Workspace\Exception\UserAlreadyBelongsToAnotherWorkspaceException;
use App\Domain\Workspace\Repository\WorkspaceInvitationRepositoryInterface;
use App\Domain\Workspace\Repository\WorkspaceMemberRepositoryInterface;
use App\Domain\Workspace\Service\SeatAvailability;
use App\Tests\Application\ReflectionHelperTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

#[AllowMockObjectsWithoutExpectations]
final class AcceptInvitationUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;

    private WorkspaceInvitationRepositoryInterface&MockObject $invitationRepository;
    private UserRepositoryInterface&MockObject $userRepository;
    private WorkspaceMemberRepositoryInterface&MockObject $memberRepository;
    private SeatAvailability&MockObject $seatAvailability;
    private AcceptInvitationUseCase $useCase;

    protected function setUp(): void
    {
        $this->invitationRepository = $this->createMock(WorkspaceInvitationRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->memberRepository = $this->createMock(WorkspaceMemberRepositoryInterface::class);
        // Par défaut un mock renvoie 0 pour les deux → 0 > 0 est faux, le siège passe.
        $this->seatAvailability = $this->createMock(SeatAvailability::class);

        $transactionManager = $this->createStub(TransactionManagerInterface::class);
        $transactionManager->method('transactional')->willReturnCallback(static fn (callable $cb) => $cb());

        $this->useCase = new AcceptInvitationUseCase(
            $this->invitationRepository,
            $this->userRepository,
            $this->memberRepository,
            $transactionManager,
            $this->createStub(EventDispatcherInterface::class),
            $this->seatAvailability,
        );
    }

    private function invitation(InvitationStatus $status = InvitationStatus::PENDING, bool $workspaceActive = true): WorkspaceInvitation
    {
        $workspace = $this->createEntityState(Workspace::class, [
            'name' => 'Cabinet',
            'slugId' => 'wrk_1',
            'isActive' => $workspaceActive,
            'members' => new ArrayCollection(),
        ]);

        return $this->createEntityState(WorkspaceInvitation::class, [
            'id' => Uuid::v7(),
            'slugId' => 'wrk_inv_1',
            'email' => 'collab@cabinet.fr',
            'firstName' => 'Marie',
            'lastName' => 'Curie',
            'invitedRole' => InvitedRole::ROLE_WORKSPACE_COLLAB,
            'invitationStatus' => $status,
            'workspace' => $workspace,
            'magicLinkToken' => WorkspaceInvitation::hashToken('plain-token'),
            'magicLinkTokenExpiresAt' => new \DateTimeImmutable('+1 hour'),
        ]);
    }

    public function testCreatesUserAndMemberForANewEmail(): void
    {
        $invitation = $this->invitation();
        $this->invitationRepository->method('findBySlugId')->willReturn($invitation);
        $this->userRepository->method('findByEmail')->willReturn(null);

        $this->userRepository->expects($this->once())->method('save');
        $this->memberRepository->expects($this->once())->method('save');

        $user = ($this->useCase)('wrk_inv_1');

        self::assertSame('collab@cabinet.fr', $user->email);
        self::assertSame(InvitationStatus::ACCEPTED, $invitation->invitationStatus);
    }

    public function testAttachesAnExistingUserAsMemberWithoutCreatingAnAccount(): void
    {
        $invitation = $this->invitation();
        $existing = $this->createEntityState(User::class, ['id' => Uuid::v7(), 'email' => 'collab@cabinet.fr', 'isActif' => true]);

        $this->invitationRepository->method('findBySlugId')->willReturn($invitation);
        $this->userRepository->method('findByEmail')->willReturn($existing);
        $this->memberRepository->method('findByWorkspaceAndUser')->willReturn(null);

        $this->userRepository->expects($this->never())->method('save');
        $this->memberRepository->expects($this->once())->method('save');

        $user = ($this->useCase)('wrk_inv_1');

        self::assertSame($existing, $user);
    }

    public function testRejectsWhenExistingUserBelongsToAnotherWorkspace(): void
    {
        $invitation = $this->invitation();
        $existing = $this->createEntityState(User::class, ['id' => Uuid::v7(), 'email' => 'collab@cabinet.fr', 'isActif' => true]);

        $otherWorkspace = $this->createEntityState(Workspace::class, [
            'name' => 'Autre cabinet',
            'slugId' => 'wrk_other',
            'members' => new ArrayCollection(),
        ]);
        $foreignMembership = $this->createEntityState(WorkspaceMember::class, ['workspace' => $otherWorkspace]);

        $this->invitationRepository->method('findBySlugId')->willReturn($invitation);
        $this->userRepository->method('findByEmail')->willReturn($existing);
        $this->memberRepository->method('findByUser')->willReturn([$foreignMembership]);

        $this->userRepository->expects($this->never())->method('save');
        $this->memberRepository->expects($this->never())->method('save');
        $this->invitationRepository->expects($this->never())->method('save');

        $this->expectException(UserAlreadyBelongsToAnotherWorkspaceException::class);
        ($this->useCase)('wrk_inv_1');
    }

    public function testRejectsWhenTheExistingUserAccountIsDisabled(): void
    {
        $invitation = $this->invitation();
        $disabled = $this->createEntityState(User::class, ['id' => Uuid::v7(), 'email' => 'collab@cabinet.fr', 'isActif' => false]);

        $this->invitationRepository->method('findBySlugId')->willReturn($invitation);
        $this->userRepository->method('findByEmail')->willReturn($disabled);

        $this->memberRepository->expects($this->never())->method('save');
        $this->invitationRepository->expects($this->never())->method('save');

        $this->expectException(CannotAcceptInvitationException::class);
        ($this->useCase)('wrk_inv_1');
    }

    public function testDoesNotDuplicateMemberWhenUserIsAlreadyInTheWorkspace(): void
    {
        $invitation = $this->invitation();
        $existing = $this->createEntityState(User::class, ['id' => Uuid::v7(), 'email' => 'collab@cabinet.fr', 'isActif' => true]);
        $member = $this->createEntityState(WorkspaceMember::class, []);

        $this->invitationRepository->method('findBySlugId')->willReturn($invitation);
        $this->userRepository->method('findByEmail')->willReturn($existing);
        $this->memberRepository->method('findByWorkspaceAndUser')->willReturn($member);

        $this->memberRepository->expects($this->never())->method('save');
        $this->invitationRepository->expects($this->once())->method('save');

        ($this->useCase)('wrk_inv_1');
    }

    public function testRejectsAnInvitationThatIsNoLongerPending(): void
    {
        $this->invitationRepository->method('findBySlugId')->willReturn($this->invitation(InvitationStatus::ACCEPTED));

        $this->userRepository->expects($this->never())->method('save');
        $this->memberRepository->expects($this->never())->method('save');

        $this->expectException(InvitationAlreadyUsedException::class);
        ($this->useCase)('wrk_inv_1');
    }

    public function testRejectsWhenAnotherIdentityIsAlreadySignedIn(): void
    {
        $this->invitationRepository->method('findBySlugId')->willReturn($this->invitation());

        $this->userRepository->expects($this->never())->method('save');
        $this->memberRepository->expects($this->never())->method('save');

        $this->expectException(CannotAcceptInvitationException::class);
        ($this->useCase)('wrk_inv_1', 'someone.else@cabinet.fr');
    }

    public function testAcceptsWhenTheSignedInIdentityIsTheRecipient(): void
    {
        $this->invitationRepository->method('findBySlugId')->willReturn($this->invitation());
        $this->userRepository->method('findByEmail')->willReturn(null);

        $user = ($this->useCase)('wrk_inv_1', 'COLLAB@cabinet.FR');

        self::assertSame('collab@cabinet.fr', $user->email);
    }

    public function testRejectsWhenTheWorkspaceIsSuspended(): void
    {
        $this->invitationRepository->method('findBySlugId')->willReturn($this->invitation(workspaceActive: false));

        $this->userRepository->expects($this->never())->method('save');

        $this->expectException(CannotAcceptInvitationException::class);
        ($this->useCase)('wrk_inv_1');
    }

    public function testRejectsWhenTheSeatQuotaIsExceeded(): void
    {
        $this->invitationRepository->method('findBySlugId')->willReturn($this->invitation());
        $this->seatAvailability->method('usedSeats')->willReturn(5);
        $this->seatAvailability->method('allowedSeats')->willReturn(2);

        $this->userRepository->expects($this->never())->method('save');
        $this->memberRepository->expects($this->never())->method('save');

        $this->expectException(SeatLimitReachedException::class);
        ($this->useCase)('wrk_inv_1');
    }
}
