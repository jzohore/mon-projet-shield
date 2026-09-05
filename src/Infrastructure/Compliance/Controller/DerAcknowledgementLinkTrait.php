<?php

declare(strict_types=1);

namespace App\Infrastructure\Compliance\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Comportements communs aux contrôleurs publics du lien d'accusé de réception
 * du DER (`/der/{token}...`) : format du jeton, anti-abus, en-têtes de sécurité.
 */
trait DerAcknowledgementLinkTrait
{
    private const string TOKEN_REQUIREMENT = '[0-9a-f]{64}';

    private function enforceLimiter(RateLimiterFactory $limiter, Request $request, string $tooManyMessage): void
    {
        if (!$limiter->create($request->getClientIp() ?? 'unknown')->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException(message: $tooManyMessage);
        }
    }

    /**
     * Le jeton étant dans l'URL, on évite qu'il fuite via un en-tête Referer
     * si un lien externe venait à être ajouté un jour sur ces pages.
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
}
