<?php

namespace App\Application\Screening\UseCase;

use App\Domain\Screening\Repository\ScreeningAuditRepositoryInterface;
use App\Infrastructure\Screening\Message\GenerateScreeningPdfMessage;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

readonly class GenerateScreeningPdfUseCase
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private ScreeningAuditRepositoryInterface $screeningAuditRepository,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(string $slugId): void
    {
        $screening = $this->screeningAuditRepository->findOneBySlug($slugId);
        Assert::notNull($screening);
        $screening->markAsProcessed();
        $this->screeningAuditRepository->save($screening);
        $this->messageBus->dispatch(new GenerateScreeningPdfMessage($screening->slugId, $screening->pdfPath));

    }
}
