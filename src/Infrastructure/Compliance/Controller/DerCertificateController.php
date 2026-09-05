<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Controller;

use App\Application\Compliance\UseCase\ComplianceDocument\DER\ResolveDerAcknowledgementLinkUseCase;
use App\Domain\Compliance\Entity\DerAcknowledgement;
use App\Domain\Compliance\Exception\AcknowledgementLinkException;
use App\Domain\Port\DocumentStorageInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Sert l'attestation PDF d'accusé de réception du DER depuis la page publique.
 */
#[AsController]
#[Route(
    path: '/der/{token}/certificate',
    name: 'app_der_certificate',
    requirements: ['token' => self::TOKEN_REQUIREMENT],
    methods: ['GET'],
)]
final class DerCertificateController extends AbstractController
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

        $acknowledgement = $document->acknowledgementInForce();
        if (!$acknowledgement instanceof DerAcknowledgement || null === $acknowledgement->certificateStoragePath) {
            throw $this->createNotFoundException();
        }

        return new Response($this->storage->getContents($acknowledgement->certificateStoragePath), Response::HTTP_OK, $this->securityHeaders([
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="attestation-accuse-reception-der.pdf"',
            'Cache-Control' => 'private, no-store',
        ]));
    }
}
