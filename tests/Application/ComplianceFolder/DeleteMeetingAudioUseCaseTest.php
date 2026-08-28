<?php

declare(strict_types=1);

namespace App\Tests\Application\ComplianceFolder;

use App\Application\Compliance\UseCase\MeetingRecord\DeleteMeetingAudioUseCase;
use App\Domain\Compliance\Entity\MeetingRecording;
use App\Domain\Compliance\Event\MeetingAudioDeletedEvent;
use App\Domain\Compliance\Repository\MeetingRecordRepositoryInterface;
use App\Domain\User\Entity\User;
use App\Domain\Workspace\Service\CurrentUserProvider;
use App\Tests\Application\ReflectionHelperTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

final class DeleteMeetingAudioUseCaseTest extends TestCase
{
    use ReflectionHelperTrait;
    private MeetingRecordRepositoryInterface&MockObject $repository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private CurrentUserProvider&MockObject $userProvider;
    private DeleteMeetingAudioUseCase $useCase;

    protected function setUp(): void
    {
        // 1. Initialisation des mocks d'infrastructure
        $this->repository = $this->createMock(MeetingRecordRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->userProvider = $this->createMock(CurrentUserProvider::class);

        // 2. Instanciation du Use Case à tester
        $this->useCase = new DeleteMeetingAudioUseCase(
            $this->repository,
            $this->eventDispatcher,
            $this->userProvider
        );
    }

    public function testInvokeDeletesAudioSavesAndDispatchesEvent(): void
    {
        // --- ARRANGE (Préparation des données) ---

        // Simulation de l'utilisateur connecté
        $user = $this->createEntityState(User::class, ['email' => 'cgp@kysure.com']);

        $this->userProvider->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $recordingId = Uuid::v4();
        $s3Path = 'documents/meetings/comp_fol_123/audio.webm';

        /** @var MeetingRecording $recording */
        $recording = $this->createEntityState(MeetingRecording::class, [
            'id' => $recordingId,
            's3Path' => $s3Path,
            'audioDeletedAt' => null,
        ]);

        // Le repository doit sauvegarder l'entité
        $this->repository->expects($this->once())
            ->method('save')
            ->with($recording);

        // L'EventDispatcher doit expédier l'événement avec les bonnes données
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (MeetingAudioDeletedEvent $event): bool => $event->recordingId === $recordingId->toString()
                && $event->filePath === $s3Path
                && $event->deletedByEmail === $user->email));

        // --- ACT ---
        ($this->useCase)($recording);

        $this->assertTrue($recording->hasAudioBeenDeleted());
        $this->assertInstanceOf(\DateTimeImmutable::class, $recording->audioDeletedAt);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testInvokeThrowsExceptionWhenRecordingIdIsNull(): void
    {
        // --- ARRANGE ---
        $user = $this->createEntityState(User::class, ['email' => 'cgp@kysure.com']);

        $this->userProvider->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $recording = $this->createEntityState(MeetingRecording::class, [
            'id' => null,
            's3Path' => 'documents/meetings/audio.webm',
            'audioDeletedAt' => null,
        ]);

        $this->eventDispatcher->expects($this->never())->method('dispatch');

        // L'exception levée par Webmozart\Assert est une InvalidArgumentException
        $this->expectException(\InvalidArgumentException::class);

        // --- ACT ---
        ($this->useCase)($recording);
    }
}
