<?php

namespace App\Infrastructure\Tracking\Controller;

use App\Application\Tracking\DTO\Request\ClickTrackingDto;
use App\Application\Tracking\UseCase\TrackClickUseCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route(path: '/track-click', name: 'api_track_click', methods: ['POST'])]
class TrackingController
{
    public function __invoke(
        #[MapRequestPayload]
        ClickTrackingDto $dto,
        TrackClickUseCase $useCase,
        Request $request,
        RequestStack $requestStack,
    ): Response {
        // Les données serveur qui ne peuvent pas être "falsifiées" par le front
        $dto->userAgent = $request->headers->get('User-Agent');
        $dto->ipAddress = $request->getClientIp();

        // On lance la machine
        $useCase->execute($dto);

        // On retourne un code 204 (No Content) car le navigateur n'a besoin d'aucune réponse visuelle
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
