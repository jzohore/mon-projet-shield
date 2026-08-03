<?php

declare(strict_types=1);

namespace App\Infrastructure\Ocr\Listener;

use App\Domain\Ocr\Event\OcrEvent;
use App\Infrastructure\Ocr\Message\ProcessOcrMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener]
readonly class DispatchToOcrListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(OcrEvent $event): void
    {
        $this->messageBus->dispatch(
            new ProcessOcrMessage($event->kycDocument->slugId)
        );
    }
}
