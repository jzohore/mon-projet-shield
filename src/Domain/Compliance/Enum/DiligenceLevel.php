<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Enum;

enum DiligenceLevel: string
{
    case SIMPLIFIED = 'SIMPLIFIED';
    case STANDARD = 'STANDARD';
    case ENHANCED = 'ENHANCED';
    case REJECTED = 'REJECTED';

    public function requiresSeniorApproval(): bool
    {
        return self::ENHANCED === $this;
    }

    /**
     * Le label prêt à être affiché dans ton interface (ex: Badges Tailwind).
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::SIMPLIFIED => 'Vigilance allégée',
            self::STANDARD => 'Vigilance normale',
            self::ENHANCED => 'Vigilance renforcée',
            self::REJECTED => 'Rupture de la relation',
        };
    }
}
