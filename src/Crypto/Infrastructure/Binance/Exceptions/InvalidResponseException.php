<?php

declare(strict_types=1);

namespace Rates\Crypto\Infrastructure\Binance\Exceptions;

class InvalidResponseException extends ApiException
{
    public static function createFromHttpCode(int $statusCode, string $body): self
    {
        return new self("Invalid HTTP response: $statusCode. Body: $body");
    }

    public static function createFromInvalidJson(string $body): self
    {
        return new self("Invalid JSON response: $body");
    }

    public static function createFromMissingFields(string $missingFields): self
    {
        return new self("Missing required fields in response: $missingFields");
    }
}
