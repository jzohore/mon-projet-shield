<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Controller;

use App\Application\Compliance\UseCase\ComplianceDocument\DER\MarkDerAsOpenedUseCase;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\MarkDerAsRejectedUseCase;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\MarkDerAsSignedUseCase;
use App\Domain\Shared\Exception\AbstractDomainException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Webmozart\Assert\Assert;

class DocuSealWebhookController extends AbstractController
{
    public function __construct(
        private readonly MarkDerAsOpenedUseCase $markDerAsOpenedUseCase,
        private readonly MarkDerAsSignedUseCase $markDerAsSignedUseCase,
        private readonly MarkDerAsRejectedUseCase $markDerAsRejectedUseCase,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/api/docuseal/webhook', name: 'api_docuseal_webhook', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        try {
            // Fail Fast : Si le JSON est invalide, ça pète tout de suite
            $payload = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);

            // 🛡️ Guard : Garantir au runtime et à PHPStan que le JSON décodé est bien un dictionnaire (array)
            Assert::isArray($payload, 'Le payload DocuSeal doit être un objet JSON valide.');
        } catch (\JsonException $e) {
            $this->logger->error('Webhook DocuSeal : JSON invalide reçu', ['error' => $e->getMessage()]);

            return new Response('Invalid Payload', Response::HTTP_BAD_REQUEST);
        } catch (\InvalidArgumentException $e) {
            $this->logger->error('Webhook DocuSeal : Format du payload inattendu', ['error' => $e->getMessage()]);

            return new Response('Invalid Payload Format', Response::HTTP_BAD_REQUEST);
        }

        // On récupère les identifiants de base
        $eventType = $payload['event_type'] ?? null;
        $submissionId = $payload['data']['submission_id'] ?? $payload['data']['id'] ?? null;

        if (null === $submissionId) {
            $this->logger->warning('Webhook DocuSeal : Submission ID manquant');

            return new Response('OK', Response::HTTP_OK); // On répond 200 pour que DocuSeal ne retry pas en boucle
        }

        try {
            // Aiguillage selon le type d'événement
            if ('form.viewed' === $eventType) {
                $this->handleFormViewed((string) $submissionId, $payload);
            } elseif ('form.declined' === $eventType) {
                $this->handleFormDeclined((string) $submissionId, $payload);
            } elseif (($payload['data']['status'] ?? null) === 'completed') {
                $this->handleFormCompleted((string) $submissionId, $payload);
            } else {
                $this->logger->info('Webhook DocuSeal : Événement ignoré', ['event_type' => $eventType]);
            }
        } catch (AbstractDomainException $exception) {
            $this->logger->warning('Dossier introuvable ou erreur domaine sur webhook', [
                'submissionId' => $submissionId,
                'exception' => $exception->getMessage(),
            ]);
        } catch (\Exception $exception) {
            $this->logger->critical('Erreur inattendue lors du webhook DocuSeal', [
                'submissionId' => $submissionId,
                'exception' => $exception->getMessage(),
            ]);
        }

        // Toujours répondre 200 OK en fin de traitement (Idempotence asynchrone)
        return new Response('OK', Response::HTTP_OK);
    }

    /**
     * Traite l'ouverture du document par le client.
     *
     * @param array<string, mixed> $payload
     *
     * @throws \DateMalformedStringException
     */
    private function handleFormViewed(string $submissionId, array $payload): void
    {
        $openedAtString = $payload['data']['opened_at'] ?? null;
        $openedAt = is_string($openedAtString) ? new \DateTimeImmutable($openedAtString) : new \DateTimeImmutable();

        ($this->markDerAsOpenedUseCase)($submissionId, openedAt: $openedAt);
    }

    /**
     * Traite le refus de signature du document.
     *
     * @param array<string, mixed> $payload
     *
     * @throws \DateMalformedStringException
     */
    private function handleFormDeclined(string $submissionId, array $payload): void
    {
        $declinedAtString = $payload['data']['declined_at'] ?? null;
        $declinedAt = is_string($declinedAtString) ? new \DateTimeImmutable($declinedAtString) : new \DateTimeImmutable();

        $declineReason = is_string($payload['data']['decline_reason'] ?? null) ? $payload['data']['decline_reason'] : null;
        ($this->markDerAsRejectedUseCase)($submissionId, $declinedAt, $declineReason);
    }

    /**
     * Traite la signature finale du document.
     *
     * @param array<string, mixed> $payload
     *
     * @throws \DateMalformedStringException
     */
    private function handleFormCompleted(string $submissionId, array $payload): void
    {
        $completedAtString = $payload['data']['completed_at'] ?? null;
        $completedAt = is_string($completedAtString) ? new \DateTimeImmutable($completedAtString) : new \DateTimeImmutable();

        $documentUrl = $payload['data']['documents'][0]['url'] ?? null;
        $auditLogUrl = $payload['data']['audit_log_url'] ?? null;

        ($this->markDerAsSignedUseCase)(
            $submissionId,
            documentUrl: (string) $documentUrl,
            auditLogUrl: (string) $auditLogUrl,
            completedAt: $completedAt
        );
    }
}
