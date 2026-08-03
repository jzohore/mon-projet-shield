<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Component;

use Symfony\UX\LiveComponent\Attribute\LiveProp;

trait LiveFlashTrait
{
    #[LiveProp]
    public ?string $actionMessage = null;

    #[LiveProp]
    public string $actionMessageType = 'success'; // 'success', 'error', 'warning', 'info'

    /**
     * Ajoute un message flash qui sera rendu par le LiveComponent.
     */
    protected function addLiveFlash(string $type, string $message): void
    {
        $this->actionMessageType = $type;
        $this->actionMessage = $message;
    }

    /**
     * Optionnel : À appeler au début de tes #[LiveAction] pour éviter
     * que l'ancien message ne reste affiché si la nouvelle action n'en génère pas.
     */
    protected function clearLiveFlash(): void
    {
        $this->actionMessage = null;
    }
}
