<?php

declare(strict_types=1);

namespace App\Application\Compliance\UseCase\MeetingRecord;

use App\Domain\Compliance\Entity\MeetingRecording;
use App\Domain\Compliance\Event\MeetingAudioDeletedEvent;
use App\Domain\Compliance\Repository\MeetingRecordRepositoryInterface;
use App\Domain\Workspace\Service\CurrentUserProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

final readonly class DeleteMeetingAudioUseCase
{
    public function __construct(
        private MeetingRecordRepositoryInterface $repository,
        private EventDispatcherInterface $eventDispatcher,
        private CurrentUserProvider $userProvider,
    ) {
    }

    public function __invoke(MeetingRecording $recording): void
    {
        $recording->markAudioAsDeleted();

        $this->repository->save($recording);

        $user = $this->userProvider->getUser();

        Assert::notNull($recording->id);
        $this->eventDispatcher->dispatch(new MeetingAudioDeletedEvent(
            recordingId: $recording->id->toString(),
            filePath: $recording->s3Path,
            deletedByEmail: $user->email
        ));
    }
}
