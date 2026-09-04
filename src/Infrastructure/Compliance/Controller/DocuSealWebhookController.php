<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Controller;

use App\Application\Compliance\UseCase\ComplianceDocument\DER\MarkDerAsOpenedUseCase;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\MarkDerAsRejectedUseCase;
use App\Application\Compliance\UseCase\ComplianceDocument\DER\MarkDerAsSignedUseCase;
use App\Domain\Shared\Exception\AbstractDomainException;
use App\Infrastructure\DocuSeal\DocuSealSignatureVerifier;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/api/docuseal/webhook', name: 'api_docuseal_webhook', methods: ['POST'])]
readonly class DocuSealWebhookController
{
    public function __construct(
        private DocuSealSignatureVerifier $signatureVerifier,
        private MarkDerAsOpenedUseCase $markDerAsOpenedUseCase,
        private MarkDerAsSignedUseCase $markDerAsSignedUseCase,
        private MarkDerAsRejectedUseCase $markDerAsRejectedUseCase,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        // Les octets bruts servent à la fois à la vérification de signature et au
        // décodage JSON — on les lit une seule fois pour ne pas signer A et traiter B.
        $rawPayload = $request->getContent();

        // 1. Authentification de l'origine du webhook. Sans ça, n'importe qui peut
        //    faire constater par KYSURE la signature d'un DER.
        if (!$this->signatureVerifier->verify(
            $rawPayload,
            $request->headers->get('X-Docuseal-Signature'),
            $request->headers->get('X-Kysure-Webhook-Token'),
        )) {
            $this->logger->warning('Webhook DocuSeal : signature invalide, requête rejetée.', [
                'remote_ip' => $request->getClientIp(),
            ]);

            return new Response('Invalid signature', Response::HTTP_UNAUTHORIZED);
        }

        // 2. Décodage — la signature est valide, mais le corps peut être inexploitable.
        try {
            $payload = json_decode($rawPayload, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $this->logger->warning('Webhook DocuSeal : JSON invalide reçu.');

            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        }

        if (!is_array($payload)) {
            $this->logger->warning('Webhook DocuSeal : payload JSON inattendu (pas un objet).');

            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        }

        $eventType = is_string($payload['event_type'] ?? null) ? $payload['event_type'] : null;
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        // `submission.*` : l'identifiant est `data.id`. `form.*` : c'est `data.submission_id`.
        $rawSubmissionId = str_starts_with((string) $eventType, 'submission.')
            ? ($data['id'] ?? null)
            : ($data['submission_id'] ?? $data['id'] ?? null);

        if (!is_numeric($rawSubmissionId)) {
            $this->logger->warning('Webhook DocuSeal : identifiant de soumission absent ou invalide.', [
                'event_type' => $eventType,
            ]);

            // 200 : rejouer ne changera rien.
            return new Response('OK', Response::HTTP_OK);
        }

        $submissionId = (string) (int) $rawSubmissionId;

        try {
            if ('form.viewed' === $eventType) {
                $this->handleFormViewed($submissionId, $data);
            } elseif ('form.declined' === $eventType) {
                $this->handleFormDeclined($submissionId, $data);
            } elseif ('completed' === ($data['status'] ?? null)) {
                $this->handleFormCompleted($submissionId, $data);
            } else {
                $this->logger->info('Webhook DocuSeal : événement ignoré.', ['event_type' => $eventType]);
            }
        } catch (AbstractDomainException $exception) {
            // Erreur métier attendue (document introuvable…) : inutile de faire retenter DocuSeal.
            $this->logger->warning('Webhook DocuSeal : erreur domaine.', [
                'submission_id' => $submissionId,
                'exception' => $exception::class,
            ]);
        } catch (\Throwable $exception) {
            // Erreur inattendue : on veut que DocuSeal rejoue — une signature à
            // valeur juridique ne doit pas être perdue en silence.
            $this->logger->critical('Webhook DocuSeal : erreur inattendue.', [
                'submission_id' => $submissionId,
                'exception' => $exception::class,
            ]);

            return new Response('Internal error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new Response('OK', Response::HTTP_OK);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleFormViewed(string $submissionId, array $data): void
    {
        ($this->markDerAsOpenedUseCase)($submissionId, openedAt: $this->readDate($data['opened_at'] ?? null));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleFormDeclined(string $submissionId, array $data): void
    {
        $declineReason = is_string($data['decline_reason'] ?? null) ? $data['decline_reason'] : null;

        ($this->markDerAsRejectedUseCase)($submissionId, $this->readDate($data['declined_at'] ?? null), $declineReason);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleFormCompleted(string $submissionId, array $data): void
    {
        $documentUrl = $data['documents'][0]['url'] ?? null;
        $auditLogUrl = $data['audit_log_url'] ?? null;

        ($this->markDerAsSignedUseCase)(
            $submissionId,
            documentUrl: is_string($documentUrl) ? $documentUrl : '',
            auditLogUrl: is_string($auditLogUrl) ? $auditLogUrl : '',
            completedAt: $this->readDate($data['completed_at'] ?? null),
        );
    }

    private function readDate(mixed $value): \DateTimeImmutable
    {
        if (is_string($value) && '' !== $value) {
            try {
                return new \DateTimeImmutable($value);
            } catch (\DateMalformedStringException) {
                // On retombe sur « maintenant » plutôt que de faire échouer le webhook.
            }
        }

        return new \DateTimeImmutable();
    }
}
