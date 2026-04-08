<?php

namespace App\Infrastructure\Shared\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('siret', $this->formatSiret(...)),
        ];
    }

    public function formatSiret(string $siret): string
    {
        // On nettoie les espaces éventuels et on formate : 3 3 3 5
        $siret = str_replace(' ', '', $siret);
        if (strlen($siret) !== 14) {
            return $siret;
        }

        return sprintf(
            '%s %s %s %s',
            substr($siret, 0, 3),
            substr($siret, 3, 3),
            substr($siret, 6, 3),
            substr($siret, 9, 5)
        );
    }
}
