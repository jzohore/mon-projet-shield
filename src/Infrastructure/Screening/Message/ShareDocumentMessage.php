<?php

namespace App\Infrastructure\Screening\Message;

readonly class ShareDocumentMessage
{
    public function __construct(
        public string $recipientEmail,
        public string $auditSlugId, // L'ID du dossier/rapport partagé
        public string $senderSlugId // L'ID de l'utilisateur qui a cliqué sur envoyer
    ) {}
}
