<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Listener\DER;

use App\Domain\Compliance\Event\DerAcknowledgedEvent;
use App\Infrastructure\Compliance\Message\GenerateDerAcknowledgementCertificateMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener]
readonly class GenerateAcknowledgementCertificateListener
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    public function __invoke(DerAcknowledgedEvent $event): void
    {
        $this->messageBus->dispatch(
            new GenerateDerAcknowledgementCertificateMessage($event->getAcknowledgementSlugId())
        );
    }
}
