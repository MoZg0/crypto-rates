<?php

declare(strict_types=1);

namespace Rates\Common\Presentation\Http\EventListeners;

use Error;
use Rates\Common\Presentation\Http\Enums\ResponseCode;
use Rates\Common\Presentation\Http\Responses\ErrorResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Throwable;

final class ExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        [$statusCode, $errorResponse] = $this->map($exception);

        $response = new JsonResponse($errorResponse, $statusCode);
        $event->setResponse($response);
    }

    /**
     * @return array{0:int,1:ErrorResponse}
     */
    private function map(Throwable $exception): array
    {
        if ($exception instanceof UnprocessableEntityHttpException || $exception instanceof ValidationFailedException) {
            $errors = $this->extractValidationErrors($exception);

            return [
                Response::HTTP_UNPROCESSABLE_ENTITY,
                new ErrorResponse(
                    errorCode: ResponseCode::VALIDATION_ERROR->value,
                    message: 'Validation error.',
                    errors: $errors,
                ),
            ];
        }

        if ($exception instanceof UnauthorizedHttpException) {
            return [
                Response::HTTP_UNAUTHORIZED,
                new ErrorResponse(
                    errorCode: ResponseCode::UNAUTHORIZED->value,
                    message: $exception->getMessage() !== '' ? $exception->getMessage() : 'Unauthorized'
                ),
            ];
        }

        if ($exception instanceof AccessDeniedHttpException) {
            return [
                Response::HTTP_FORBIDDEN,
                new ErrorResponse(
                    errorCode: ResponseCode::ACCESS_DENIED->value,
                    message: 'Access denied'
                ),
            ];
        }

        if ($exception instanceof HttpException) {
            return [
                $exception->getStatusCode(),
                new ErrorResponse(
                    errorCode: ResponseCode::HTTP_ERROR->value,
                    message: $exception->getMessage() !== '' ? $exception->getMessage() : 'HTTP error'
                ),
            ];
        }

        if ($exception instanceof Error) {
            return [
                Response::HTTP_INTERNAL_SERVER_ERROR,
                new ErrorResponse(
                    errorCode: ResponseCode::SERVER_ERROR->value,
                    message: 'Server error.'
                ),
            ];
        }

        return [
            Response::HTTP_INTERNAL_SERVER_ERROR,
            new ErrorResponse(
                errorCode: ResponseCode::UNHANDLED_ERROR->value,
                message: $exception->getMessage() !== '' ? $exception->getMessage() : 'Unhandled error'
            ),
        ];
    }

    /**
     * @return list<array{property: string, message: string, invalidValue: string}>
     */
    private function extractValidationErrors(Throwable $exception): array
    {
        $violations = null;

        $previous = $exception->getPrevious();
        if ($previous instanceof ValidationFailedException) {
            $violations = $previous->getViolations();
        } elseif ($exception instanceof ValidationFailedException) {
            $violations = $exception->getViolations();
        }

        if ($violations === null) {
            return [];
        }

        $errors = [];
        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $errors[] = [
                'property' => $violation->getPropertyPath(),
                'message' => (string) $violation->getMessage(),
                'invalidValue' => self::stringifyInvalidValue($violation->getInvalidValue()),
            ];
        }

        return $errors;
    }

    private static function stringifyInvalidValue(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        if (is_object($value)) {
            return 'object<' . $value::class . '>';
        }

        if (is_array($value)) {
            return 'array';
        }

        return get_debug_type($value);
    }
}
