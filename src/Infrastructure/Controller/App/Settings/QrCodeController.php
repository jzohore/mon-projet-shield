<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\App\Settings;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface as GoogleAuthenticatorTwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Webmozart\Assert\Assert;

class QrCodeController extends AbstractController
{
    public function __construct(private readonly TokenStorageInterface $tokenStorage, private readonly GoogleAuthenticatorInterface $googleAuthenticator)
    {
    }

    #[Route('/members/qr/ga', name: 'qr_code_ga')]
    public function displayGoogleAuthenticatorQrCode(): Response
    {
        Assert::notNull($this->tokenStorage->getToken());
        $user = $this->tokenStorage->getToken()->getUser();
        if (!$user instanceof GoogleAuthenticatorTwoFactorInterface) {
            throw new NotFoundHttpException('Cannot display QR code');
        }

        return $this->displayQrCode($this->googleAuthenticator->getQRContent($user));
    }

    private function displayQrCode(string $qrCodeContent): Response
    {
        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            data: $qrCodeContent,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 200,
            margin: 0,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );
        $result = $builder->build();

        return new Response($result->getString(), Response::HTTP_OK, ['Content-Type' => 'image/png']);
    }
}
