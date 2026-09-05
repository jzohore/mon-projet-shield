<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Controller;

use App\Application\Compliance\UseCase\ComplianceDocument\DER\ResolveDerAcknowledgementLinkUseCase;
use App\Domain\Compliance\Exception\AcknowledgementLinkException;
use App\Domain\Port\DocumentStorageInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Sert le PDF du DER depuis la page publique d'accusé de réception. Sans
 * limiteur, un jeton qui fuite permettrait un nombre illimité de
 * téléchargements S3.
 */
#[AsController]
#[Route(
    path: '/der/{token}/pdf',
    name: 'app_der_pdf',
    requirements: ['token' => self::TOKEN_REQUIREMENT],
    methods: ['GET'],
)]
final class DerPdfController extends AbstractController
{
    use DerAcknowledgementLinkTrait;

    public function __construct(
        private readonly ResolveDerAcknowledgementLinkUseCase $resolveLink,
        private readonly DocumentStorageInterface $storage,
        private readonly RateLimiterFactory $readRateLimiter,
    ) {
    }

    public function __invoke(Request $request, string $token): Response
    {
        $this->enforceLimiter($this->readRateLimiter, $request, 'Trop de consultations. Merci de réessayer plus tard.');

        try {
            $document = ($this->resolveLink)($token);
        } catch (AcknowledgementLinkException) {
            throw $this->createNotFoundException();
        }

        if (null === $document->storagePath) {
            throw $this->createNotFoundException();
        }

        return new Response($this->storage->getContents($document->storagePath), Response::HTTP_OK, $this->securityHeaders([
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="document-entree-en-relation.pdf"',
            'Cache-Control' => 'private, no-store',
        ]));
    }
}
