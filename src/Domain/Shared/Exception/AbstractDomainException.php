<?php

namespace App\Domain\Shared\Exception;

use App\Domain\Shared\Enum\ErrorCode;

abstract class AbstractDomainException extends \DomainException implements DomainExceptionInterface
{
    /**
     * @param string $message
     * @param ErrorCode $errorCode
     * @param int $statusCode
     * @param array<mixed, string> $payload
     */
    public function __construct(
        string $message,
        private readonly ErrorCode $errorCode,
        private readonly int $statusCode = 400, // Par défaut, une erreur métier est souvent une 400
        private readonly array $payload = []
    ) {
        parent::__construct($message);
    }

    public function getErrorCode(): ErrorCode
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<mixed, string>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
}
