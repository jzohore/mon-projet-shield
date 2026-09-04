<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Controller;

use App\Application\Compliance\DTO\Request\AcknowledgeDerRequest;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\AcknowledgeDerUseCase;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\ResolveDerAcknowledgementLinkUseCase;
use App\Domain\Compliance\Entity\DerAcknowledgement;
use App\Domain\Compliance\Exception\AcknowledgementLinkException;
use App\Domain\Compliance\ValueObject\DerStatement;
use App\Domain\Port\DocumentStorageInterface;
use App\Infrastructure\Compliance\Form\DerAcknowledgementType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Page publique d'accusé de réception du DER. Accès par lien nominatif à jeton
 * (256 bits, haché en base) : le client n'a pas encore de compte.
 */
final class DerAcknowledgementController extends AbstractController
{
    private const string TOKEN_REQUIREMENT = '[0-9a-f]{64}';

    public function __construct(
        private readonly ResolveDerAcknowledgementLinkUseCase $resolveLink,
        private readonly AcknowledgeDerUseCase $acknowledgeDer,
        private readonly DocumentStorageInterface $storage,
        private readonly RateLimiterFactory $derAcknowledgementLimiter,
    ) {
    }

    #[Route(
        path: '/der/{token}',
        name: 'app_der_acknowledge',
        requirements: ['token' => self::TOKEN_REQUIREMENT],
        methods: ['GET', 'POST'],
    )]
    public function acknowledge(Request $request, string $token): Response
    {
        try {
            $document = ($this->resolveLink)($token);
        } catch (AcknowledgementLinkException $exception) {
            return $this->render('app/der/invalid.html.twig', ['reason' => $exception->reason], new Response('', Response::HTTP_GONE));
        }

        $inForce = $document->acknowledgementInForce();
        if ($inForce instanceof DerAcknowledgement) {
            return $this->render('app/der/acknowledged.html.twig', [
                'acknowledgement' => $inForce,
                'pdf_url' => $this->generateUrl('app_der_pdf', ['token' => $token]),
            ]);
        }

        $acknowledgeRequest = new AcknowledgeDerRequest();
        $acknowledgeRequest->token = $token;
        $form = $this->createForm(DerAcknowledgementType::class, $acknowledgeRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->derAcknowledgementLimiter->create($request->getClientIp() ?? 'unknown')->consume()->isAccepted()) {
                throw new TooManyRequestsHttpException(message: 'Trop de tentatives. Merci de réessayer plus tard.');
            }

            $acknowledgeRequest->ipAddress = $request->getClientIp();
            $userAgent = (string) $request->headers->get('User-Agent', '');
            $acknowledgeRequest->userAgent = '' !== $userAgent ? mb_substr($userAgent, 0, 255) : null;

            try {
                ($this->acknowledgeDer)($acknowledgeRequest);
            } catch (\DomainException $exception) {
                $this->addFlash('error', $exception->getMessage());
            }

            // POST-Redirect-GET : le rechargement affiche l'écran de confirmation.
            return $this->redirectToRoute('app_der_acknowledge', ['token' => $token]);
        }

        return $this->render('app/der/acknowledge.html.twig', [
            'form' => $form,
            'statement' => DerStatement::current(),
            'pdf_url' => $this->generateUrl('app_der_pdf', ['token' => $token]),
        ]);
    }

    #[Route(
        path: '/der/{token}/pdf',
        name: 'app_der_pdf',
        requirements: ['token' => self::TOKEN_REQUIREMENT],
        methods: ['GET'],
    )]
    public function pdf(string $token): Response
    {
        try {
            $document = ($this->resolveLink)($token);
        } catch (AcknowledgementLinkException) {
            throw $this->createNotFoundException();
        }

        if (null === $document->storagePath) {
            throw $this->createNotFoundException();
        }

        return new Response($this->storage->getContents($document->storagePath), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="document-entree-en-relation.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
