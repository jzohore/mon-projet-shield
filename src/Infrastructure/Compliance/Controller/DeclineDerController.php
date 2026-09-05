<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Controller;

use App\Application\Compliance\DTO\Request\DeclineDerRequest;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\DeclineDerUseCase;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\ResolveDerAcknowledgementLinkUseCase;
use App\Domain\Compliance\Exception\AcknowledgementLinkException;
use App\Infrastructure\Compliance\Form\DerDeclineType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Le client déclare, depuis la page publique du DER, ne pas reconnaître le
 * document reçu.
 */
#[AsController]
#[Route(
    path: '/der/{token}/decline',
    name: 'app_der_decline',
    requirements: ['token' => self::TOKEN_REQUIREMENT],
    methods: ['POST'],
)]
final class DeclineDerController extends AbstractController
{
    use DerAcknowledgementLinkTrait;

    public function __construct(
        private readonly ResolveDerAcknowledgementLinkUseCase $resolveLink,
        private readonly DeclineDerUseCase $declineDer,
        private readonly RateLimiterFactory $rateLimiter,
        private readonly RateLimiterFactory $readRateLimiter,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request, string $token): Response
    {
        $this->enforceLimiter($this->readRateLimiter, $request, 'Trop de consultations. Merci de réessayer plus tard.');

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
            $this->enforceLimiter($this->rateLimiter, $request, 'Trop de tentatives. Merci de réessayer plus tard.');

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
}
