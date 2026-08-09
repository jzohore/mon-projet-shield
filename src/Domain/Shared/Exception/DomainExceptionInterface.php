<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

use App\Domain\Shared\Enum\ErrorCode;

interface DomainExceptionInterface extends \Throwable
{
    /**
     * Le code d'erreur interne, immuable (ex: "WORKSPACE_NOT_FOUND").
     */
    public function getErrorCode(): ErrorCode;

    /**
     * Le code de statut HTTP approprié (ex: 404, 400, 403).
     */
    public function getStatusCode(): int;

    /**
     * Des données supplémentaires utiles au front-end (ex: ["id_recherche" => "123"]).
     *
     * @return array<mixed, string>
     */
    public function getPayload(): array;
}
