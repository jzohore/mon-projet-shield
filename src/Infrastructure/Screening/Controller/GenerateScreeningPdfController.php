<?php

namespace App\Infrastructure\Screening\Controller;

use App\Application\Screening\UseCase\GenerateScreeningPdfUseCase;
use App\Application\Screening\UseCase\GetScreeningInfo;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsController]
#[Route(path: '/app/screening/generate/pdf/{slugId}', name: 'app_screening_generate_pdf', methods: ['POST'])]
readonly class GenerateScreeningPdfController
{
    public function __construct(
        private GenerateScreeningPdfUseCase $generateScreeningPdfUseCase,
        private GetScreeningInfo $getScreeningInfo,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(string $slugId): RedirectResponse
    {
        ($this->generateScreeningPdfUseCase)($slugId);
        $screening = ($this->getScreeningInfo)($slugId);
        return new RedirectResponse(url: $this->urlGenerator->generate('app_screening_show', ['slugId' => $screening->slugId]));
    }
}
