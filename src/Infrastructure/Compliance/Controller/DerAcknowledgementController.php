<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Controller;

use App\Application\Compliance\DTO\Request\AcknowledgeDerRequest;
use App\Application\Compliance\DTO\Request\DeclineDerRequest;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\AcknowledgeDerUseCase;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\DeclineDerUseCase;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\ResolveDerAcknowledgementLinkUseCase;
use App\Domain\Compliance\Entity\DerAcknowledgement;
use App\Domain\Compliance\Exception\AcknowledgementLinkException;
use App\Domain\Compliance\ValueObject\DerStatement;
use App\Domain\Port\DocumentStorageInterface;
use App\Infrastructure\Compliance\Form\DerAcknowledgementType;
use App\Infrastructure\Compliance\Form\DerDeclineType;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Psr\Log\LoggerInterface;
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
        private readonly DeclineDerUseCase $declineDer,
        private readonly DocumentStorageInterface $storage,
        private readonly RateLimiterFactory $derAcknowledgementLimiter,
        private readonly RateLimiterFactory $derAcknowledgementReadLimiter,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Limiteur strict : actions qui mutent l'état (accuser réception, refuser).
     */
    private function enforceRateLimit(Request $request): void
    {
        if (!$this->derAcknowledgementLimiter->create($request->getClientIp() ?? 'unknown')->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException(message: 'Trop de tentatives. Merci de réessayer plus tard.');
        }
    }

    /**
     * Limiteur large : simple consultation (page, PDF, attestation). Sans lui,
     * un token qui fuite permet un nombre illimité de téléchargements S3.
     */
    private function enforceReadRateLimit(Request $request): void
    {
        if (!$this->derAcknowledgementReadLimiter->create($request->getClientIp() ?? 'unknown')->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException(message: 'Trop de consultations. Merci de réessayer plus tard.');
        }
    }

    /**
     * En-têtes communs des réponses de cette page publique : le jeton étant
     * dans l'URL, on évite qu'il fuite via un en-tête Referer si un lien
     * externe venait à être ajouté un jour sur ces pages.
     *
     * @param array<string, string> $extra
     *
     * @return array<string, string>
     */
    private function securityHeaders(array $extra = []): array
    {
        return $extra + [
            'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex, nofollow',
        ];
    }

    #[Route(
        path: '/der/{token}',
        name: 'app_der_acknowledge',
        requirements: ['token' => self::TOKEN_REQUIREMENT],
        methods: ['GET', 'POST'],
    )]
    public function acknowledge(Request $request, string $token): Response
    {
        $this->enforceReadRateLimit($request);

        try {
            $document = ($this->resolveLink)($token);
        } catch (AcknowledgementLinkException $exception) {
            return $this->render('app/der/invalid.html.twig', ['reason' => $exception->reason], new Response('', Response::HTTP_GONE));
        }

        $workspace = $document->folder->workspace;

        $inForce = $document->acknowledgementInForce();
        if ($inForce instanceof DerAcknowledgement) {
            return $this->render('app/der/acknowledged.html.twig', [
                'acknowledgement' => $inForce,
                'token' => $token,
                'workspace' => $workspace,
                'pdf_url' => $this->generateUrl('app_der_pdf', ['token' => $token]),
            ]);
        }

        if ($document->isDerDeclined()) {
            return $this->render('app/der/declined.html.twig', [
                'reason' => $document->derDeclineReason,
                'workspace' => $workspace,
            ]);
        }

        $acknowledgeRequest = new AcknowledgeDerRequest();
        $acknowledgeRequest->token = $token;
        $form = $this->createForm(DerAcknowledgementType::class, $acknowledgeRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->enforceRateLimit($request);

            $acknowledgeRequest->ipAddress = $request->getClientIp();
            $userAgent = (string) $request->headers->get('User-Agent', '');
            $acknowledgeRequest->userAgent = '' !== $userAgent ? mb_substr($userAgent, 0, 255) : null;

            try {
                ($this->acknowledgeDer)($acknowledgeRequest);
            } catch (UniqueConstraintViolationException $exception) {
                // Double soumission concurrente (double-clic) : l'un des deux
                // POST a gagné, l'autre échoue sur l'index unique. Bénin — le
                // redirect (nouvelle requête, nouvel EntityManager) affichera
                // le bon état final. Rien à signaler au client.
                $this->logger->info('Accusé de réception DER : collision bénigne sur double soumission.', [
                    'token_hash' => hash('sha256', $token),
                    'error' => $exception->getMessage(),
                ]);
            } catch (\DomainException $exception) {
                $this->addFlash('error', $exception->getMessage());
            } catch (\InvalidArgumentException $exception) {
                // Garde de précondition (Assert) : donnée du dossier incohérente
                // (ex. e-mail de contact absent). Filet de sécurité pour ne
                // jamais renvoyer un 500 brut sur cette page publique.
                $this->logger->error('Accusé de réception DER : précondition invalide.', [
                    'token_hash' => hash('sha256', $token),
                    'error' => $exception->getMessage(),
                ]);
                $this->addFlash('error', 'Une information de votre dossier est incomplète. Contactez votre conseiller.');
            }

            // POST-Redirect-GET : le rechargement affiche l'écran de confirmation.
            return $this->redirectToRoute('app_der_acknowledge', ['token' => $token]);
        }

        return $this->render('app/der/acknowledge.html.twig', [
            'form' => $form,
            'decline_form' => $this->createForm(DerDeclineType::class, new DeclineDerRequest(), [
                'action' => $this->generateUrl('app_der_decline', ['token' => $token]),
            ])->createView(),
            'statement' => DerStatement::current(),
            'workspace' => $workspace,
            'pdf_url' => $this->generateUrl('app_der_pdf', ['token' => $token]),
        ]);
    }

    #[Route(
        path: '/der/{token}/decline',
        name: 'app_der_decline',
        requirements: ['token' => self::TOKEN_REQUIREMENT],
        methods: ['POST'],
    )]
    public function decline(Request $request, string $token): Response
    {
        $this->enforceReadRateLimit($request);

        try {
            ($this->resolveLink)($token);
        } catch (AcknowledgementLinkException $exception) {
            return $this->render('app/der/invalid.html.twig', ['reason' => $exception->reason], new Response('', Response::HTTP_GONE));
        }

        $declineRequest = new DeclineDerRequest();
        $declineRequest->token = $token;
        $form = $this->createForm(DerDeclineType::class, $declineRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->enforceRateLimit($request);

            try {
                ($this->declineDer)($token, $declineRequest->reason);
            } catch (\DomainException $exception) {
                $this->addFlash('error', $exception->getMessage());
            } catch (\InvalidArgumentException $exception) {
                $this->logger->error('Refus DER : précondition invalide.', [
                    'token_hash' => hash('sha256', $token),
                    'error' => $exception->getMessage(),
                ]);
                $this->addFlash('error', 'Une information de votre dossier est incomplète. Contactez votre conseiller.');
            }
        }

        return $this->redirectToRoute('app_der_acknowledge', ['token' => $token]);
    }

    #[Route(
        path: '/der/{token}/pdf',
        name: 'app_der_pdf',
        requirements: ['token' => self::TOKEN_REQUIREMENT],
        methods: ['GET'],
    )]
    public function pdf(Request $request, string $token): Response
    {
        $this->enforceReadRateLimit($request);

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

    #[Route(
        path: '/der/{token}/certificate',
        name: 'app_der_certificate',
        requirements: ['token' => self::TOKEN_REQUIREMENT],
        methods: ['GET'],
    )]
    public function certificate(Request $request, string $token): Response
    {
        $this->enforceReadRateLimit($request);

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
