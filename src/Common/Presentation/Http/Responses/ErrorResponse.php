<?php

declare(strict_types=1);

namespace Rates\Common\Presentation\Http\Responses;

use Rates\Common\Presentation\Http\Enums\ResponseStatusText;
use JsonSerializable;

class ErrorResponse implements JsonSerializable
{
    private string $status;

    /** @var array<string, mixed> */
    private array $data = [];

    /**
     * @param list<array{property: string, message: string, invalidValue: string}> $errors
     * @param list<mixed> $trace
     */
    public function __construct(
        private string $errorCode,
        private string $message = 'Unhandled error',
        array $errors = [],
        array $trace = [],
    ) {
        $this->status = ResponseStatusText::ERROR->value;

        if ($errors !== []) {
            $this->data = ['errors' => $errors];
        }

        if ($trace !== []) {
            $this->data['trace'] = $trace;
        }
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return array{status: string, errorCode: string, message: string, data: array<string, mixed>}
     */
    public function jsonSerialize(): array
    {
        return [
            'status' => $this->status,
            'errorCode' => $this->errorCode,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }
}
