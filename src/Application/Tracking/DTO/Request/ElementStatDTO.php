<?php

namespace App\Application\Tracking\DTO\Request;

readonly class ElementStatDTO
{
    public function __construct(
        public string $elementName,
        public int $clickCount
    ) {}
}
