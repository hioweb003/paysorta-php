<?php

namespace Hiotech\PaysortaPhp\Exceptions;

use Exception;
use Throwable;

class PaysortaException extends Exception
{
    protected int $statusCode;
    protected array $responseBody;

    public function __construct(string $message, int $statusCode = 0, array $responseBody = [], ?Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);

        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): array
    {
        return $this->responseBody;
    }
}
