<?php

namespace App\Infrastructure\Pdf;

use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Environment;

readonly class GotenbergPdfGenerator implements PdfGeneratorInterface
{
    public function __construct(
        private Environment $twig,
        private HttpClientInterface $httpClient,
        private string $gotenbergUrl,
    ) {}

    /**
     * @param string $template
     * @param array<string, mixed> $context
     * @return string
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function generateFromHtml(string $template, array $context): string
    {
        /**
         * On utilise la Fluent API du bundle :
         * 1. html() : Prépare une requête Chromium
         * 2. content() : Gère le rendu Twig en interne
         * 3. generate() : Envoie la requête au serveur Gotenberg
         * 4. getContent() : Récupère le binaire (nécessaire pour le stockage S3)
         */
        // 1. Génère le HTML
        $htmlContent = $this->twig->render($template, $context);

        // 2. Préparation du fichier
        $formFields = [
            'files' => [
                'index.html' => new DataPart($htmlContent, 'index.html', 'text/html'),
            ],
        ];
        $formData = new FormDataPart($formFields);

        try {
            // 3. Appel direct au serveur Gotenberg avec un TRES LONG timeout (60 secondes)
            $response = $this->httpClient->request('POST', $this->gotenbergUrl, [
                'headers' => $formData->getPreparedHeaders()->toArray(),
                'body' => $formData->bodyToIterable(),
                'timeout' => 60, // On lui laisse une minute pour réfléchir !
            ]);

            // 4. On récupère le contenu. S'il y a une erreur 400 ou 500, Symfony plantera ICI
            $pdfContent = $response->getContent();

            return $pdfContent;

        } catch (TransportExceptionInterface $e) {
            // S'il y a un problème réseau (timeout, gotenberg éteint...)
            throw new \Exception("Erreur réseau vers Gotenberg : " . $e->getMessage());
        } catch (\Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface $e) {
            // Si Gotenberg plante en essayant de rendre le PDF (ex: HTML invalide)
            throw new \Exception("Gotenberg a planté (Erreur " . $e->getResponse()->getStatusCode() . ") : " . $e->getResponse()->getContent(false));
        }
    }
}
