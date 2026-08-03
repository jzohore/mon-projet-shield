<?php

declare(strict_types=1);

namespace App\Application\Screening\UseCase;

use App\Domain\Screening\Entity\ScreeningAudit;
use App\Domain\Screening\Repository\ScreeningAuditRepositoryInterface;
use App\Infrastructure\Screening\Message\GenerateScreeningPdfMessage;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class GenerateScreeningPdfUseCase
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ScreeningAuditRepositoryInterface $screeningAuditRepository,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(ScreeningAudit $audit): void
    {
        $audit->markAsProcessed();
        $this->screeningAuditRepository->save($audit);
        $this->messageBus->dispatch(new GenerateScreeningPdfMessage($audit->slugId, $audit->pdfPath));
    }
}
